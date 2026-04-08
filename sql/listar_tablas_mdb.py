"""
Listar tablas y columnas de Datos.mdb (requiere motor Access ODBC).
Uso: python listar_tablas_mdb.py
     python listar_tablas_mdb.py Lista Coberturas
"""
import sys
import pyodbc

path = r"c:\Control Salud\Datos.mdb"
conn = pyodbc.connect(
    f"DRIVER={{Microsoft Access Driver (*.mdb, *.accdb)}};DBQ={path};PWD=control333;"
)
cur = conn.cursor()

if len(sys.argv) > 1:
    tname = sys.argv[1]
    print("COLUMNAS:", tname)
    for row in cur.columns(table=tname):
        print(row.column_name, row.type_name, row.column_size)
else:
    tables = sorted(
        {
            r.table_name
            for r in cur.tables(tableType="TABLE")
            if not r.table_name.startswith("MSys")
        }
    )
    for t in tables:
        print(t)

conn.close()
