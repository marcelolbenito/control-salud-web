# Migrar Control Salud (Access) → MySQL

## La forma más simple (archivo `.sql` de datos, sin CSV)

No hace falta exportar a CSV a mano. Generás **un solo archivo SQL** con los datos del `.mdb` y lo ejecutás en MySQL.

1. **Una vez**, creá las tablas vacías en MySQL (Workbench, Docker, etc.):
   - `../schema_mysql.sql`
   - `schema_listas_minimo.sql`

2. **Generá** el archivo de datos desde Access (solo necesitás `pyodbc`, no hace falta `pymysql`):

   ```text
   cd sql/migracion
   pip install pyodbc
   python generar_migration_sql.py
   ```

   Se crea **`migration_data_listas.sql`** en la misma carpeta (lo podés abrir con el Bloc de notas y revisarlo).

3. **Importá** ese archivo en MySQL:
   - **Workbench**: *File → Open SQL Script* → elegí `migration_data_listas.sql` → rayito ⚡ ejecutar.
   - **Línea de comandos** (ajustá puerto/clave):

     ```text
     mysql -h 127.0.0.1 -P 3307 -u root -p control_salud < migration_data_listas.sql
     ```

Listo: los catálogos **Lista** quedan cargados. El CSV era solo una alternativa manual; **no es obligatorio**.

---

## Opción A — Script Python directo a MySQL (sin archivo intermedio)

1. Instalar dependencias y copiar configuración:

   ```text
   cd sql/migracion
   pip install -r requirements.txt
   copy config.example.env config.env
   ```

2. Editar `config.env`: ruta a `Datos.mdb`, contraseña del .mdb si aplica, y datos de MySQL (host, puerto **3307** si usás Docker, usuario, clave, base `control_salud`).

3. En MySQL (Workbench, CLI o Docker):

   ```text
   SOURCE .../schema_listas_minimo.sql;
   ```

4. Ejecutar:

   ```text
   python migrar_mdb_a_mysql.py
   ```

Esto vacía y vuelve a cargar las tablas `lista_*` del script. **No** copia todavía `Pacientes` ni el resto de las 121 tablas: habría que ampliar el script o usar la opción B/C.

---

## Opción A2 — Backup SQL Server (`Datos.bak`) -> MySQL completo (todas las tablas)

Si ya restauraste `Datos.bak` en SQL Server (por ejemplo en `.\SQLEXPRESS`, base `Datos`), este es el camino para tener un MySQL con datos reales del último backup.

1. Preparar entorno:

   ```text
   cd sql/migracion
   pip install -r requirements.txt
   copy config.example.env config.env
   ```

2. Editar `config.env` para SQL Server:
   - `SQLSERVER_HOST=.\SQLEXPRESS`
   - `SQLSERVER_DATABASE=Datos`
   - `SQLSERVER_TRUSTED=1` (si usás autenticación Windows)
   - `SQLSERVER_TRUST_CERT=1` (evita error SSL del driver ODBC 18)
   - `SQLSERVER_TO_MYSQL_OUT=...migration_from_sqlserver_full.sql`

3. Generar SQL completo (estructura + datos de **todas** las tablas):

   ```text
   python sqlserver_backup_to_mysql_sql.py
   ```

4. Importar en MySQL:

   ```text
   mysql -h 127.0.0.1 -P 3307 -u root -p control_salud < migration_from_sqlserver_full.sql
   ```

Notas:
- El script conserva nombres reales de tablas/columnas (incluyendo espacios) usando backticks.
- Si querés probar con pocas tablas primero: `SQLSERVER_ONLY_TABLE_REGEX=^(Pacientes|Lista Doctores|Agenda Turnos)$`
- Si el archivo de salida crece mucho, bajá o subí `SQLSERVER_INSERT_BATCH_SIZE` según rendimiento/memoria.

---

## Opción B — CSV desde Access

1. Abrí `Datos.mdb` con **Microsoft Access**.
2. Por cada tabla: clic derecho → **Exportar** → **Excel** o **Texto** (CSV), codificación UTF-8 si podés.
3. En MySQL: `CREATE TABLE` acorde (o desde nuestros `.sql`) y luego:

   ```sql
   LOAD DATA LOCAL INFILE 'C:/ruta/lista_coberturas.csv'
   INTO TABLE lista_coberturas
   FIELDS TERMINATED BY ';' ENCLOSED BY '"'
   LINES TERMINATED BY '\n'
   IGNORE 1 ROWS;
   ```

   Ajustá separador y rutas según el CSV real. En Windows a veces hace falta habilitar `local_infile` en el servidor MySQL.

---

## Opción C — Asistente de migración (Workbench / otras)

En **MySQL Workbench**: menú **Database** → **Migration Wizard** (según versión puede migrar desde ODBC hacia MySQL). Elegís un DSN ODBC al archivo Access y mapeás tablas. Útil para una migración grande de una sola vez; revisá tipos y nombres después.

---

## Pacientes y tablas hijas

- Tras tener **columnas** alineadas (`migration_002`) y **listas** cargadas, la tabla `Pacientes` se puede llenar con:
  - un segundo script Python (mismo patrón: `SELECT` en Access, `INSERT` en MySQL, con mapeo campo a campo), o
  - export CSV de Access e import en MySQL.

- Respetá **orden** si hay claves foráneas: primero `lista_*`, después `pacientes`, después tablas `Pacientes *` / `Agenda *` según dependencias.

---

## Comprobar datos en Access desde consola

Desde la raíz del proyecto:

```text
python sql/listar_tablas_mdb.py
python sql/listar_tablas_mdb.py "Lista Coberturas"
```
