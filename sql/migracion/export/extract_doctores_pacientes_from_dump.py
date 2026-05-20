"""
Extrae doctores y pacientes desde datos_legacy_Pacientes_Doctores_Turnos.sql
(marcadores -- Lista Doctores: / -- Pacientes:, sin índices de línea fijos).

Uso:
  python extract_doctores_pacientes_from_dump.py
"""
from __future__ import annotations

from pathlib import Path

HERE = Path(__file__).resolve().parent
FULL = HERE / "datos_legacy_Pacientes_Doctores_Turnos.sql"
OUT_DOCTORES = HERE / "datos_legacy_doctores.sql"
OUT_PACIENTES = HERE / "datos_legacy_pacientes.sql"


def sanitize_sql_strings(text: str) -> str:
    out: list[str] = []
    in_str = False
    i = 0
    n = len(text)
    while i < n:
        ch = text[i]
        if ch == "'" and not in_str:
            in_str = True
            out.append(ch)
        elif ch == "'" and in_str:
            if i + 1 < n and text[i + 1] == "'":
                out.append("''")
                i += 1
            else:
                in_str = False
            out.append(ch)
        elif in_str and ch in "\r\n":
            out.append(" ")
        else:
            out.append(ch)
        i += 1
    return "".join(out)


def extract_between(full: str, start_marker: str, end_marker: str | None) -> str:
    start = full.find(start_marker)
    if start < 0:
        raise SystemExit(f"No se encontró: {start_marker!r}")
    if end_marker:
        end = full.find(end_marker, start + len(start_marker))
        if end < 0:
            raise SystemExit(f"No se encontró fin: {end_marker!r}")
        block = full[start:end]
    else:
        block = full[start:]
    return sanitize_sql_strings(block).replace(");,", ");")


def extract_schema(full: str, table: str, next_table: str | None) -> str:
    start = full.find(f"DROP TABLE IF EXISTS `{table}`")
    if start < 0:
        raise SystemExit(f"No schema para `{table}`")
    if next_table:
        end = full.find(f"DROP TABLE IF EXISTS `{next_table}`", start)
        if end < 0:
            raise SystemExit(f"No fin de schema `{table}`")
        return full[start:end].strip()
    tail = full.find(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", start)
    if tail < 0:
        raise SystemExit(f"CREATE incompleto para `{table}`")
    return full[start : tail + len(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;")].strip()


def write_part(path: Path, schema: str, data: str) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as out:
        out.write("-- Generado por extract_doctores_pacientes_from_dump.py\n")
        out.write("SET NAMES utf8mb4;\n")
        out.write("SET FOREIGN_KEY_CHECKS = 0;\n\n")
        out.write(schema + "\n\n")
        out.write(data.strip() + "\n\n")
        out.write("SET FOREIGN_KEY_CHECKS = 1;\n")
    mb = path.stat().st_size / 1024 / 1024
    print(f"OK {path.name} ({mb:.2f} MB)")


def main() -> None:
    if not FULL.is_file():
        raise SystemExit(f"No existe: {FULL}")

    print(f"Leyendo {FULL.name} ...")
    full = FULL.read_text(encoding="utf-8", errors="replace")

    schema_d = extract_schema(full, "Lista Doctores", "Pacientes")
    data_d = extract_between(full, "-- Lista Doctores:", "-- Pacientes:")
    write_part(OUT_DOCTORES, schema_d, data_d)

    schema_p = extract_schema(full, "Pacientes", None)
    data_p = extract_between(full, "-- Pacientes:", None)
    if "SET FOREIGN_KEY" in data_p:
        data_p = data_p.split("SET FOREIGN_KEY")[0].strip()
    write_part(OUT_PACIENTES, schema_p, data_p)


if __name__ == "__main__":
    main()
