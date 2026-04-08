"""
Genera un .sql para clonar SQL Server -> MySQL (todas las tablas + datos).

Caso Control Salud:
1) Restaurar Datos.bak en SQL Server (ej: .\\SQLEXPRESS, DB: Datos).
2) Ejecutar este script para generar migration_from_sqlserver_full.sql.
3) Importar ese .sql en MySQL.

Uso:
  cd sql/migracion
  pip install -r requirements.txt
  copy config.example.env config.env
  python sqlserver_backup_to_mysql_sql.py
"""
from __future__ import annotations

import os
import re
import sys
from datetime import date, datetime, time
from decimal import Decimal
from typing import Iterable

try:
    import pyodbc
except ImportError:
    print("Falta pyodbc. Instalá dependencias: pip install -r requirements.txt")
    sys.exit(1)


def load_env(path: str) -> dict[str, str]:
    cfg: dict[str, str] = {}
    if not os.path.isfile(path):
        print(f"No existe {path}. Copiá config.example.env a config.env")
        sys.exit(1)
    with open(path, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            cfg[k.strip()] = v.strip().strip('"').strip("'")
    return cfg


def q_ident(name: str) -> str:
    return "`" + name.replace("`", "``") + "`"


def sql_literal(val) -> str:
    if val is None:
        return "NULL"
    if isinstance(val, bool):
        return "1" if val else "0"
    if isinstance(val, (int, float, Decimal)):
        return str(val)
    if isinstance(val, datetime):
        return "'" + val.strftime("%Y-%m-%d %H:%M:%S") + "'"
    if isinstance(val, date):
        return "'" + val.strftime("%Y-%m-%d") + "'"
    if isinstance(val, time):
        return "'" + val.strftime("%H:%M:%S") + "'"
    if isinstance(val, (bytes, bytearray, memoryview)):
        b = bytes(val)
        return "X'" + b.hex().upper() + "'"
    s = str(val)
    s = s.replace("\\", "\\\\").replace("'", "''")
    return "'" + s + "'"


def mysql_type(
    data_type: str,
    char_max_len: int | None,
    precision: int | None,
    scale: int | None,
) -> str:
    t = data_type.lower()
    if t in {"int"}:
        return "INT"
    if t in {"bigint"}:
        return "BIGINT"
    if t in {"smallint"}:
        return "SMALLINT"
    if t in {"tinyint"}:
        return "TINYINT"
    if t == "bit":
        return "TINYINT(1)"
    if t in {"decimal", "numeric", "money", "smallmoney"}:
        p = precision or 18
        s = scale or 0
        return f"DECIMAL({p},{s})"
    if t == "float":
        return "DOUBLE"
    if t == "real":
        return "FLOAT"
    if t == "date":
        return "DATE"
    if t == "time":
        return "TIME"
    if t in {"datetime", "smalldatetime", "datetime2"}:
        return "DATETIME"
    if t == "datetimeoffset":
        return "VARCHAR(40)"
    if t in {"char", "nchar"}:
        n = char_max_len if char_max_len and char_max_len > 0 else 1
        return f"CHAR({min(n, 255)})"
    if t in {"varchar", "nvarchar"}:
        if char_max_len is None or char_max_len < 0:
            return "LONGTEXT"
        n = max(char_max_len, 1)
        if n <= 65535:
            return f"VARCHAR({n})"
        return "LONGTEXT"
    if t in {"text", "ntext", "xml"}:
        return "LONGTEXT"
    if t in {"uniqueidentifier"}:
        return "CHAR(36)"
    if t in {"binary", "varbinary", "image", "rowversion", "timestamp"}:
        if char_max_len is None or char_max_len < 0:
            return "LONGBLOB"
        if char_max_len <= 255:
            return f"VARBINARY({char_max_len})"
        if char_max_len <= 65535:
            return "BLOB"
        return "LONGBLOB"
    return "LONGTEXT"


def sqlserver_connect(cfg: dict[str, str]):
    server = cfg.get("SQLSERVER_HOST", r".\SQLEXPRESS")
    database = cfg.get("SQLSERVER_DATABASE", "Datos")
    trusted = cfg.get("SQLSERVER_TRUSTED", "1") == "1"
    trust_cert = cfg.get("SQLSERVER_TRUST_CERT", "1") == "1"
    user = cfg.get("SQLSERVER_USER", "")
    password = cfg.get("SQLSERVER_PASSWORD", "")

    parts = [
        "DRIVER={ODBC Driver 18 for SQL Server}",
        f"SERVER={server}",
        f"DATABASE={database}",
        "Encrypt=yes",
        f"TrustServerCertificate={'yes' if trust_cert else 'no'}",
    ]
    if trusted:
        parts.append("Trusted_Connection=yes")
    else:
        parts.append(f"UID={user}")
        parts.append(f"PWD={password}")
    conn_str = ";".join(parts) + ";"
    return pyodbc.connect(conn_str)


def list_tables(cur, schema: str) -> list[str]:
    cur.execute(
        """
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
        """,
        schema,
    )
    return [r[0] for r in cur.fetchall()]


def table_columns(cur, schema: str, table: str):
    cur.execute(
        """
        SELECT
            COLUMN_NAME,
            DATA_TYPE,
            CHARACTER_MAXIMUM_LENGTH,
            NUMERIC_PRECISION,
            NUMERIC_SCALE,
            IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION
        """,
        schema,
        table,
    )
    return cur.fetchall()


def table_primary_key(cur, schema: str, table: str) -> list[str]:
    cur.execute(
        """
        SELECT c.COLUMN_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE c
          ON c.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
         AND c.TABLE_SCHEMA = tc.TABLE_SCHEMA
         AND c.TABLE_NAME = tc.TABLE_NAME
        WHERE tc.TABLE_SCHEMA = ? AND tc.TABLE_NAME = ? AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY c.ORDINAL_POSITION
        """,
        schema,
        table,
    )
    return [r[0] for r in cur.fetchall()]


def table_identity_columns(cur, schema: str, table: str) -> set[str]:
    cur.execute(
        """
        SELECT c.name
        FROM sys.tables t
        JOIN sys.schemas s ON s.schema_id = t.schema_id
        JOIN sys.columns c ON c.object_id = t.object_id
        WHERE s.name = ? AND t.name = ? AND c.is_identity = 1
        """,
        schema,
        table,
    )
    return {r[0] for r in cur.fetchall()}


def write_schema_for_table(
    out,
    cur,
    schema: str,
    table: str,
):
    cols = table_columns(cur, schema, table)
    pk = table_primary_key(cur, schema, table)
    identities = table_identity_columns(cur, schema, table)

    out.write(f"DROP TABLE IF EXISTS {q_ident(table)};\n")
    out.write(f"CREATE TABLE {q_ident(table)} (\n")

    lines: list[str] = []
    for c in cols:
        col_name = c[0]
        col_type = mysql_type(c[1], c[2], c[3], c[4])
        nullable = c[5] == "YES"
        line = f"  {q_ident(col_name)} {col_type}"
        if col_name in identities and col_type.upper() in {"INT", "BIGINT"}:
            line += " AUTO_INCREMENT"
        line += " NULL" if nullable else " NOT NULL"
        lines.append(line)

    if pk:
        pk_cols = ", ".join(q_ident(x) for x in pk)
        lines.append(f"  PRIMARY KEY ({pk_cols})")

    out.write(",\n".join(lines))
    out.write("\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n")


def chunked(iterable: Iterable, n: int):
    batch = []
    for item in iterable:
        batch.append(item)
        if len(batch) >= n:
            yield batch
            batch = []
    if batch:
        yield batch


def write_data_for_table(
    out,
    cur,
    schema: str,
    table: str,
    batch_size: int,
) -> int:
    cols = table_columns(cur, schema, table)
    col_names = [c[0] for c in cols]
    select_cols = ", ".join(f"[{c}]" for c in col_names)
    cur.execute(f"SELECT {select_cols} FROM [{schema}].[{table}]")
    rows = cur.fetchall()
    total = len(rows)

    if total == 0:
        out.write(f"-- {table}: 0 filas\n\n")
        return 0

    out.write(f"-- {table}: {total} filas\n")
    ins_head = f"INSERT INTO {q_ident(table)} ({', '.join(q_ident(c) for c in col_names)}) VALUES\n"
    for batch in chunked(rows, batch_size):
        values_sql = []
        for r in batch:
            vals = ", ".join(sql_literal(r[i]) for i in range(len(col_names)))
            values_sql.append(f"({vals})")
        out.write(ins_head)
        out.write(",\n".join(values_sql))
        out.write(";\n")
    out.write("\n")
    return total


def main() -> None:
    base = os.path.dirname(os.path.abspath(__file__))
    cfg = load_env(os.path.join(base, "config.env"))

    schema = cfg.get("SQLSERVER_SCHEMA", "dbo")
    out_file = cfg.get(
        "SQLSERVER_TO_MYSQL_OUT",
        os.path.join(base, "migration_from_sqlserver_full.sql"),
    )
    batch_size = int(cfg.get("SQLSERVER_INSERT_BATCH_SIZE", "200"))
    only_pattern = cfg.get("SQLSERVER_ONLY_TABLE_REGEX", "").strip()
    only_re = re.compile(only_pattern, re.IGNORECASE) if only_pattern else None

    print("Conectando a SQL Server...")
    conn = sqlserver_connect(cfg)
    cur = conn.cursor()

    tables = list_tables(cur, schema)
    if only_re:
        tables = [t for t in tables if only_re.search(t)]

    if not tables:
        print("No se encontraron tablas para exportar.")
        conn.close()
        sys.exit(1)

    print(f"Generando SQL de {len(tables)} tablas -> {out_file}")
    with open(out_file, "w", encoding="utf-8", newline="\n") as out:
        out.write("-- Generado por sqlserver_backup_to_mysql_sql.py\n")
        out.write(f"-- SQL Server: {cfg.get('SQLSERVER_HOST', r'.\\SQLEXPRESS')} / DB {cfg.get('SQLSERVER_DATABASE', 'Datos')}\n")
        out.write("SET NAMES utf8mb4;\n")
        out.write("SET FOREIGN_KEY_CHECKS = 0;\n\n")

        for i, table in enumerate(tables, start=1):
            print(f"[{i}/{len(tables)}] esquema {table}")
            write_schema_for_table(out, cur, schema, table)

        out.write("-- ==================== DATOS ====================\n\n")
        total_rows = 0
        for i, table in enumerate(tables, start=1):
            print(f"[{i}/{len(tables)}] datos {table}")
            total_rows += write_data_for_table(out, cur, schema, table, batch_size)

        out.write("SET FOREIGN_KEY_CHECKS = 1;\n")

    conn.close()
    print(f"Listo. Tablas: {len(tables)} | Filas totales: {total_rows}")
    print(f"Archivo generado: {out_file}")
    print("Importar en MySQL con:")
    print(f"  mysql -h 127.0.0.1 -P 3307 -u root -p control_salud < \"{out_file}\"")


if __name__ == "__main__":
    main()
