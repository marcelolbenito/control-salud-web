import re
from pathlib import Path

path = Path(__file__).with_name("lab_schema_phpmyadmin.sql")
text = path.read_text(encoding="utf-8")
parts = re.split(r"(?=CREATE TABLE IF NOT EXISTS)", text)
header = parts[0]
blocks = [p for p in parts[1:] if p.strip()]

def table_name(block: str) -> str:
    m = re.search(r"CREATE TABLE IF NOT EXISTS `([^`]+)`", block)
    return m.group(1) if m else ""

by_name = {table_name(b): b for b in blocks if table_name(b)}
order = [
    "lab_areas",
    "lab_determinaciones",
    "lab_valores_referencia",
    "lab_perfiles",
    "lab_perfil_determinaciones",
    "lab_pedidos",
    "lab_pedido_items",
    "lab_resultados",
    "lab_resultados_historico",
    "lab_informes",
    "lab_auditoria",
    "lab_nbu_determinaciones",
    "lab_nbu_valores_os",
    "lab_lotes_os",
    "lab_lote_pedidos",
    "lab_lote_pedido_item_excluido",
    "lab_pagos",
    "lab_config",
]
out = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n"
for t in order:
    if t in by_name:
        out += by_name[t].rstrip() + "\n"
for t in sorted(set(by_name) - set(order)):
    out += by_name[t].rstrip() + "\n"
out += "\nSET FOREIGN_KEY_CHECKS=1;\n"
path.write_text(out, encoding="utf-8", newline="\n")
print("OK", len(by_name), "tables")
