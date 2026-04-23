"""
Lee Datos.mdb y genera un archivo .sql con INSERT (sin tocar CSV).

Solo necesitás: Python + pyodbc + motor Access (mismo que para abrir el .mdb).

Uso:
  cd sql/migracion
  pip install pyodbc
  python generar_migration_sql.py

Salida: migration_data_listas.sql (junto a este script)

Luego en MySQL (con tablas ya creadas: schema_listas_minimo.sql):
  mysql -h 127.0.0.1 -P 3307 -u root -p control_salud < migration_data_listas.sql
"""
from __future__ import annotations

import os
import sys
from datetime import date, datetime
from decimal import Decimal

try:
    import pyodbc
except ImportError:
    print("Instalá: pip install pyodbc")
    sys.exit(1)

# Misma ruta por defecto que el resto del proyecto
MDB_PATH = os.environ.get("MDB_PATH", r"C:\Control Salud\Datos.mdb")
MDB_PASSWORD = os.environ.get("MDB_PASSWORD", "control333")
OUT_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "migration_data_listas.sql")


def sql_literal(val) -> str:
    if val is None:
        return "NULL"
    if isinstance(val, bool):
        return "1" if val else "0"
    if isinstance(val, (int, float, Decimal)):
        return str(val)
    if isinstance(val, (datetime, date)):
        if isinstance(val, datetime):
            return "'" + val.strftime("%Y-%m-%d %H:%M:%S") + "'"
        return "'" + val.strftime("%Y-%m-%d") + "'"
    s = str(val).replace("\\", "\\\\").replace("'", "''")
    return "'" + s + "'"


def mdb_connect():
    return pyodbc.connect(
        f"DRIVER={{Microsoft Access Driver (*.mdb, *.accdb)}};"
        f"DBQ={MDB_PATH};PWD={MDB_PASSWORD};"
    )


JOBS: list[tuple[str, str, list[str], list[str]]] = [
    ("Lista Pais", "lista_pais", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ("Lista Provincia", "lista_provincia", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ("Lista Ciudad", "lista_ciudad", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    (
        "Lista Coberturas",
        "lista_coberturas",
        ["id", "prioridad", "nombre", "Porcentaje_Cobertura", "plancober"],
        ["id", "prioridad", "nombre", "porcentaje_cobertura", "plancober"],
    ),
    ("Lista Planes", "lista_planes", ["id", "idcobertura", "nombre"], ["id", "id_cobertura", "nombre"]),
    ("Lista Tipo de documento", "lista_tipo_documento", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ("Lista Ocupacion", "lista_ocupacion", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ("Lista Estado civil", "lista_estado_civil", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ("Lista Etnia", "lista_etnia", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    (
        "Lista Relacion con el paciente",
        "lista_relacion_paciente",
        ["id", "prioridad", "nombre"],
        ["id", "prioridad", "nombre"],
    ),
    (
        "Lista Estatus en el pais",
        "lista_estatus_pais",
        ["id", "prioridad", "nombre"],
        ["id", "prioridad", "nombre"],
    ),
    # lista_practicas: ver append_lista_practicas_desde_mdb() (Lista Practicas o Nomenclador)
    ("Lista Derivaciones", "lista_derivaciones", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ("Lista Sucursales", "lista_sucursales", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
]


def append_lista_practicas_desde_mdb(cur, lines: list[str]) -> None:
    """En algunos .mdb las prácticas están en [Nomenclador] en lugar de [Lista Practicas]."""
    acols = ["id", "prioridad", "nombre"]
    mcols = ["id", "prioridad", "nombre"]
    col_sql = ", ".join(f"`{c}`" for c in mcols)
    sel = ", ".join(f"[{c}]" for c in acols)
    for access_t in ("Lista Practicas", "Nomenclador"):
        try:
            cur.execute(f"SELECT {sel} FROM [{access_t}]")
            rows = cur.fetchall()
            lines.append(f"-- lista_practicas desde Access [{access_t}]")
            lines.append("DELETE FROM `lista_practicas`;")
            for row in rows:
                vals = ", ".join(sql_literal(row[i]) for i in range(len(row)))
                lines.append(f"INSERT INTO `lista_practicas` ({col_sql}) VALUES ({vals});")
            lines.append("")
            return
        except Exception:
            continue
    lines.append("-- lista_practicas: no se encontró [Lista Practicas] ni [Nomenclador] en el .mdb")
    lines.append("")


def main() -> None:
    if not os.path.isfile(MDB_PATH):
        print(f"No está el archivo: {MDB_PATH}")
        print("Definí la variable de entorno MDB_PATH si está en otra carpeta.")
        sys.exit(1)

    mdb = mdb_connect()
    lines: list[str] = [
        "-- Generado por generar_migration_sql.py — datos catálogo desde Access",
        "-- Ejecutar DESPUÉS de schema_listas_minimo.sql",
        "SET NAMES utf8mb4;",
        "SET FOREIGN_KEY_CHECKS = 0;",
        "",
    ]

    cur = mdb.cursor()
    practicas_hecho = False
    for access_t, mysql_t, acols, mcols in JOBS:
        if mysql_t == "lista_derivaciones" and not practicas_hecho:
            append_lista_practicas_desde_mdb(cur, lines)
            practicas_hecho = True
        sel = ", ".join(f"[{c}]" for c in acols)
        cur.execute(f"SELECT {sel} FROM [{access_t}]")
        rows = cur.fetchall()
        col_sql = ", ".join(f"`{c}`" for c in mcols)
        lines.append(f"DELETE FROM `{mysql_t}`;")
        for row in rows:
            vals = ", ".join(sql_literal(row[i]) for i in range(len(row)))
            lines.append(f"INSERT INTO `{mysql_t}` ({col_sql}) VALUES ({vals});")
        lines.append("")

    lines.append("SET FOREIGN_KEY_CHECKS = 1;")
    mdb.close()

    with open(OUT_FILE, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))

    print(f"Listo: {OUT_FILE}")
    print("Importá en MySQL con el cliente mysql o desde Workbench (abrir archivo y ejecutar).")


if __name__ == "__main__":
    main()
