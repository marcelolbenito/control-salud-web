# Seeds

Datos semilla del catalogo. **NO se aplican automaticamente**: hay que correrlos a mano DESPUES de que las migrations crearon las tablas.

---

## Como aplicar (Laragon + MariaDB)

Desde la terminal de Laragon, parado en la raiz del proyecto:

```cmd
mysql -u root clinica < sql\seeds\001_seed_catalogo.sql
```

Verificar:

```cmd
mysql -u root clinica -e "SELECT codigo, nombre FROM lab_areas; SELECT COUNT(*) AS determinaciones FROM lab_determinaciones; SELECT codigo, nombre FROM lab_perfiles;"
```

Resultado esperado:
- 5 areas (HEM, QC, HOR, INM, MIC)
- 30 determinaciones
- 5 perfiles (HMG, TIR, LIP, HEP, PREQ)

---

## Idempotencia

Cada seed esta envuelto en transaccion. Re-correr no duplica datos:
- Tablas con `UNIQUE(codigo)` (areas, determinaciones, perfiles): `INSERT IGNORE`.
- Tablas pivote/valores (`lab_valores_referencia`, `lab_perfil_determinaciones`): `DELETE WHERE IN (...) + INSERT` para refrescar limpio si el contenido del seed cambia.

Si en un futuro queres modificar valores existentes, **edita el archivo de seed y volve a correrlo**: las tablas pivote se rehacen, pero las tablas con `INSERT IGNORE` no se actualizan (para eso, hay que hacer `UPDATE` manual o crear un `002_*` con la modificacion).

---

## Contenido actual

### Areas (5)

| Codigo | Nombre           | Orden |
|--------|------------------|-------|
| HEM    | Hematologia      | 1     |
| QC     | Quimica clinica  | 2     |
| HOR    | Hormonas         | 3     |
| INM    | Inmunologia      | 4     |
| MIC    | Microbiologia    | 5     |

### Determinaciones (30)

**Hematologia (10):** HB, HTO, GR, GB, PLT, VCM, HCM, CHCM, RDW, VSG.
**Quimica clinica (18):** GLU, URE, CRE, AU, COL, TG, HDL, LDL, AST, ALT, BIT, BID, FAL, ALB, PT, NA, K, CA.
**Hormonas (2):** TSH, T4L.

Valores criticos cargados en: HB, GB, PLT, GLU, URE, CRE, BIT, NA, K, CA.

### Valores de referencia (~36)

Rangos de **adulto** (`edad_min_dias` / `edad_max_dias` = NULL). Separados por sexo donde clinicamente corresponde: HB, HTO, GR, VSG, CRE, AU, HDL.

### Perfiles (5)

| Codigo | Nombre               | Determinaciones                                 |
|--------|----------------------|-------------------------------------------------|
| HMG    | Hemograma completo   | HB, HTO, GR, GB, PLT, VCM, HCM, CHCM, RDW       |
| TIR    | Perfil tiroideo      | TSH, T4L                                        |
| LIP    | Perfil lipidico      | COL, TG, HDL, LDL                               |
| HEP    | Hepatograma          | AST, ALT, BIT, BID, FAL, ALB, PT                |
| PREQ   | Prequirurgico basico | GLU, URE, CRE, HB, HTO, PLT                     |

---

## Pendiente

- **Rangos pediatricos**: recien nacido, lactante, nino, adolescente.
- **Inmunologia**: VDRL, HIV, HBsAg, HCV, IgG/IgM, etc.
- **Microbiologia**: urocultivo, hemocultivo, coprocultivo, antibiograma.
- **Mas perfiles**: renal, cardiovascular, embarazo, ETS, etc.
- **Catalogos secundarios**: tipos de tubo recomendado por determinacion, condiciones de extraccion, etc.

Estas extensiones se agregan como `002_seed_*.sql`, `003_seed_*.sql`, sin tocar el `001`.
