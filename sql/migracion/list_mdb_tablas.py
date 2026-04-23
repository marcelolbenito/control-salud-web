"""
Lista tablas y columnas de Datos.mdb (útil para confirmar nombres exactos de "Lista *").

Uso:
  cd sql/migracion
  pip install pyodbc
  set MDB_PATH=C:\\ruta\\Datos.mdb
  python list_mdb_tablas.py

Opcional: filtrar por substring en el nombre de tabla:
  python list_mdb_tablas.py Lista
"""
from __future__ import annotations

import os
import sys

try:
    import pyodbc
except ImportError:
    print("Instalá: pip install pyodbc")
    sys.exit(1)

MDB_PATH = os.environ.get("MDB_PATH", os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), "Datos.mdb"))
MDB_PASSWORD = os.environ.get("MDB_PASSWORD", "control333")


def main() -> None:
    filt = sys.argv[1].lower() if len(sys.argv) > 1 else ""

    if not os.path.isfile(MDB_PATH):
        print(f"No está el archivo: {MDB_PATH}")
        print("Definí MDB_PATH o copiá Datos.mdb al directorio del proyecto.")
        sys.exit(1)

    conn = pyodbc.connect(
        f"DRIVER={{Microsoft Access Driver (*.mdb, *.accdb)}};"
        f"DBQ={MDB_PATH};PWD={MDB_PASSWORD};"
    )
    cur = conn.cursor()
    for row in cur.tables(tableType="TABLE"):
        name = row.table_name
        if filt and filt not in name.lower():
            continue
        try:
            cur2 = conn.cursor()
            cur2.execute(f"SELECT TOP 0 * FROM [{name}]")
            cols = [c[0] for c in cur2.description] if cur2.description else []
            print(f"[{name}]")
            print("  " + ", ".join(cols))
        except Exception as e:
            print(f"[{name}] (error al leer columnas: {e})")
    conn.close()


if __name__ == "__main__":
    main()
