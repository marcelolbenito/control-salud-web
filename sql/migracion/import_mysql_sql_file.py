"""
Importa un archivo .sql grande en MySQL sin depender del cliente `mysql`.

Uso:
  python import_mysql_sql_file.py
"""
from __future__ import annotations

import os
import sys

import pymysql


def load_env(path: str) -> dict[str, str]:
    cfg: dict[str, str] = {}
    if not os.path.isfile(path):
        print(f"No existe {path}. Copia config.example.env a config.env")
        sys.exit(1)
    with open(path, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            cfg[k.strip()] = v.strip().strip('"').strip("'")
    return cfg


def iter_sql_statements(sql_path: str):
    in_single = False
    in_double = False
    in_block_comment = False
    buffer: list[str] = []

    with open(sql_path, "r", encoding="utf-8") as f:
        for raw_line in f:
            line = raw_line
            i = 0
            line_len = len(line)

            while i < line_len:
                ch = line[i]
                nxt = line[i + 1] if i + 1 < line_len else ""

                if in_block_comment:
                    if ch == "*" and nxt == "/":
                        in_block_comment = False
                        i += 2
                        continue
                    i += 1
                    continue

                if not in_single and not in_double:
                    if ch == "-" and nxt == "-":
                        # Comentario de linea: ignorar hasta fin de linea.
                        break
                    if ch == "/" and nxt == "*":
                        in_block_comment = True
                        i += 2
                        continue

                if ch == "'" and not in_double:
                    if in_single and nxt == "'":
                        # Comilla escapada SQL ('') dentro de string.
                        buffer.append("''")
                        i += 2
                        continue
                    in_single = not in_single
                    buffer.append(ch)
                    i += 1
                    continue

                if ch == '"' and not in_single:
                    if in_double and nxt == '"':
                        buffer.append('""')
                        i += 2
                        continue
                    in_double = not in_double
                    buffer.append(ch)
                    i += 1
                    continue

                if ch == ";" and not in_single and not in_double:
                    stmt = "".join(buffer).strip()
                    buffer.clear()
                    if stmt:
                        yield stmt
                    i += 1
                    continue

                buffer.append(ch)
                i += 1

            if not in_block_comment:
                buffer.append("\n")

    trailing = "".join(buffer).strip()
    if trailing:
        yield trailing


def main() -> None:
    base = os.path.dirname(os.path.abspath(__file__))
    cfg = load_env(os.path.join(base, "config.env"))

    mysql_host = cfg.get("MYSQL_HOST", "127.0.0.1")
    mysql_port = int(cfg.get("MYSQL_PORT", "3307"))
    mysql_db = cfg.get("MYSQL_DATABASE", "control_salud")
    mysql_user = cfg.get("MYSQL_USER", "root")
    mysql_password = cfg.get("MYSQL_PASSWORD", "")
    sql_path = cfg.get(
        "SQLSERVER_TO_MYSQL_OUT",
        os.path.join(base, "migration_from_sqlserver_full.sql"),
    )

    if not os.path.isfile(sql_path):
        print(f"No existe el archivo SQL: {sql_path}")
        sys.exit(1)

    print(f"Conectando a MySQL {mysql_host}:{mysql_port} / DB {mysql_db} ...")
    conn = pymysql.connect(
        host=mysql_host,
        port=mysql_port,
        user=mysql_user,
        password=mysql_password,
        database=mysql_db,
        charset="utf8mb4",
        autocommit=False,
    )

    executed = 0
    try:
        with conn.cursor() as cur:
            for statement in iter_sql_statements(sql_path):
                cur.execute(statement)
                executed += 1
                if executed % 100 == 0:
                    conn.commit()
                    print(f"Sentencias ejecutadas: {executed}")
            conn.commit()
    except Exception as exc:  # noqa: BLE001
        conn.rollback()
        print(f"Error al importar en sentencia #{executed + 1}: {exc}")
        raise
    finally:
        conn.close()

    print(f"Importacion finalizada. Sentencias totales: {executed}")


if __name__ == "__main__":
    main()
