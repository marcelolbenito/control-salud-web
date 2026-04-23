"""
Copia tablas catálogo "Lista *" desde Datos.mdb hacia MySQL.

Requisitos: pip install -r requirements.txt, ODBC Access, MySQL.

Uso:
  cd sql/migracion
  pip install -r requirements.txt
  copy config.example.env config.env
  python migrar_mdb_a_mysql.py

Antes: ejecutar en MySQL schema_mysql.sql, migration_002 (opcional), schema_listas_minimo.sql
(incluye lista_practicas / lista_derivaciones / lista_sucursales). Si falla el nombre de una tabla
en Access, listá tablas con: python list_mdb_tablas.py Lista
Las prácticas suelen estar en [Lista Practicas]; en algunos .mdb la tabla es [Nomenclador] (mismo esquema id/prioridad/nombre si aplica).
"""
from __future__ import annotations

import os
import sys

try:
    import pyodbc
    import pymysql
except ImportError:
    print("Instalá: pip install -r requirements.txt")
    sys.exit(1)


def load_env(path: str) -> dict[str, str]:
    cfg: dict[str, str] = {}
    if not os.path.isfile(path):
        print(f"No existe {path}. Copiá config.example.env a config.env")
        sys.exit(1)
    with open(path, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            if "=" in line:
                k, v = line.split("=", 1)
                cfg[k.strip()] = v.strip().strip('"').strip("'")
    return cfg


def mysql_connect(cfg: dict[str, str]):
    return pymysql.connect(
        host=cfg.get("MYSQL_HOST", "127.0.0.1"),
        port=int(cfg.get("MYSQL_PORT", "3306")),
        user=cfg.get("MYSQL_USER", "root"),
        password=cfg.get("MYSQL_PASSWORD", ""),
        database=cfg.get("MYSQL_DATABASE", "control_salud"),
        charset="utf8mb4",
    )


def mdb_connect(cfg: dict[str, str]):
    path = cfg.get("MDB_PATH", r"C:\Control Salud\Datos.mdb")
    pwd = cfg.get("MDB_PASSWORD", "control333")
    return pyodbc.connect(
        f"DRIVER={{Microsoft Access Driver (*.mdb, *.accdb)}};DBQ={path};PWD={pwd};"
    )


def migrate_simple(
    mdb,
    mysql,
    access_table: str,
    mysql_table: str,
    access_cols: list[str],
    mysql_cols: list[str],
) -> int:
    sel = ", ".join(f"[{c}]" for c in access_cols)
    cur_m = mdb.cursor()
    cur_m.execute(f"SELECT {sel} FROM [{access_table}]")
    rows = cur_m.fetchall()

    cur_y = mysql.cursor()
    cur_y.execute(f"DELETE FROM `{mysql_table}`")
    col_sql = ", ".join(f"`{c}`" for c in mysql_cols)
    ph = ", ".join(["%s"] * len(mysql_cols))
    sql_ins = f"INSERT INTO `{mysql_table}` ({col_sql}) VALUES ({ph})"
    n = 0
    for row in rows:
        cur_y.execute(sql_ins, row)
        n += 1
    mysql.commit()
    return n


def main() -> None:
    base = os.path.dirname(os.path.abspath(__file__))
    cfg = load_env(os.path.join(base, "config.env"))

    print("Conectando...")
    mdb = mdb_connect(cfg)
    mysql = mysql_connect(cfg)

    jobs: list[tuple[str, str, list[str], list[str]]] = [
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
        # lista_practicas: ver bloque después del bucle (Lista Practicas o Nomenclador)
        ("Lista Derivaciones", "lista_derivaciones", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
        ("Lista Sucursales", "lista_sucursales", ["id", "prioridad", "nombre"], ["id", "prioridad", "nombre"]),
    ]

    for access_t, mysql_t, acols, mcols in jobs:
        print(f"{access_t} -> {mysql_t} ...")
        n = migrate_simple(mdb, mysql, access_t, mysql_t, acols, mcols)
        print(f"  {n} filas.")

    print("lista_practicas (Lista Practicas o Nomenclador) ...")
    pract_cols = ["id", "prioridad", "nombre"]
    practicas_ok = False
    for access_t in ("Lista Practicas", "Nomenclador"):
        try:
            n_pr = migrate_simple(mdb, mysql, access_t, "lista_practicas", pract_cols, pract_cols)
            print(f"  {n_pr} filas desde [{access_t}].")
            practicas_ok = True
            break
        except Exception as e:
            print(f"  [{access_t}]: {e}")
    if not practicas_ok:
        print("  (no se pudo leer ninguna tabla de prácticas)")

    mdb.close()
    mysql.close()
    print("Listo.")


if __name__ == "__main__":
    main()
