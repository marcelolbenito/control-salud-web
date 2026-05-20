"""
Regenera datos_legacy_turnos_fixed.sql desde el dump completo:
- Sanitiza saltos de línea dentro de strings SQL
- Corrige cierres ');,'
- Filtra Fecha >= 2026-01-01
"""
from __future__ import annotations

import re
from pathlib import Path

HERE = Path(__file__).resolve().parent
FULL = HERE / "datos_legacy_Pacientes_Doctores_Turnos.sql"
OUT = HERE / "datos_legacy_turnos_fixed.sql"
BATCH = 200
FECHA_MIN = "2026-01-01"


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


def extract_schema(full: str) -> str:
    start = full.find("DROP TABLE IF EXISTS `Agenda Turnos`")
    if start < 0:
        raise SystemExit("No se encontró DROP Agenda Turnos")
    end = full.find("DROP TABLE IF EXISTS `Lista Doctores`", start)
    if end < 0:
        raise SystemExit("No se encontró fin del CREATE Agenda Turnos")
    return full[start:end]


def extract_agenda_data(full: str) -> str:
    start = full.find("-- Agenda Turnos:")
    if start < 0:
        raise SystemExit("No se encontró bloque de datos Agenda Turnos")
    end = full.find("-- Lista Doctores:", start)
    if end < 0:
        raise SystemExit("No se encontró fin de datos Agenda Turnos")
    return full[start:end]


def extract_rows(section: str) -> list[str]:
    """Extrae tuplas (id, ...) completas del bloque de datos."""
    rows: list[str] = []
    buf: list[str] = []
    depth = 0
    in_str = False
    i = 0
    n = len(section)
    while i < n:
        ch = section[i]
        if ch == "'" and not in_str:
            in_str = True
            if depth == 0 and not buf:
                depth = 1
            buf.append(ch)
        elif ch == "'" and in_str:
            if i + 1 < n and section[i + 1] == "'":
                buf.append("''")
                i += 1
            else:
                in_str = False
            buf.append(ch)
        elif not in_str and ch == "(":
            if depth == 0:
                buf = ["("]
            else:
                buf.append(ch)
            depth += 1
        elif not in_str and ch == ")":
            buf.append(ch)
            depth -= 1
            if depth == 0:
                row = "".join(buf).strip()
                if row.startswith("("):
                    rows.append(row)
                buf = []
        elif depth > 0:
            buf.append(ch)
        i += 1
    return rows


def row_fecha(row: str) -> str | None:
    m = re.match(
        r"\(\s*\d+\s*,\s*\d+\s*,\s*-?\d+\s*,\s*'(\d{4}-\d{2}-\d{2})",
        row,
    )
    return m.group(1) if m else None


def write_batches(out, insert_header: str, rows: list[str]) -> None:
    for i in range(0, len(rows), BATCH):
        batch = rows[i : i + BATCH]
        out.write(insert_header)
        out.write(",\n".join(batch))
        out.write(";\n\n")


def main() -> None:
    if not FULL.is_file():
        raise SystemExit(f"No existe dump completo: {FULL}")

    print(f"Leyendo {FULL.name} ...")
    full = FULL.read_text(encoding="utf-8", errors="replace")
    schema_part = extract_schema(full)
    data_block = extract_agenda_data(full)
    data_block = sanitize_sql_strings(data_block)
    data_block = data_block.replace(");,", ");")

    m = re.search(
        r"^INSERT INTO `Agenda Turnos` \(.+\) VALUES\s*$",
        data_block,
        re.MULTILINE,
    )
    if not m:
        raise SystemExit("Sin INSERT header en datos Agenda")
    insert_header = m.group(0) + "\n"

    data_part = data_block[data_block.find(insert_header) :]
    all_rows = extract_rows(data_part)
    print(f"Filas en dump (Agenda): {len(all_rows)}")

    filtered = [r for r in all_rows if (f := row_fecha(r)) and f >= FECHA_MIN]
    print(f"Filas Fecha >= {FECHA_MIN}: {len(filtered)}")

    with OUT.open("w", encoding="utf-8", newline="\n") as out:
        out.write("-- Regenerado por rebuild_turnos_from_dump.py\n")
        out.write(f"-- Fuente: {FULL.name}\n")
        out.write("SET NAMES utf8mb4;\n")
        out.write("SET FOREIGN_KEY_CHECKS = 0;\n\n")
        out.write(f"-- Agenda Turnos: {len(filtered)} filas (Fecha >= {FECHA_MIN})\n")
        out.write(schema_part)
        if not schema_part.endswith("\n\n"):
            out.write("\n")
        write_batches(out, insert_header, filtered)
        out.write("SET FOREIGN_KEY_CHECKS = 1;\n")

    print(f"OK -> {OUT} ({OUT.stat().st_size / 1024 / 1024:.2f} MB)")


if __name__ == "__main__":
    main()
