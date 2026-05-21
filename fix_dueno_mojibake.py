#!/usr/bin/env python3
# Corregir mojibake en dueño.php

# Leer el archivo
with open('dueño.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Reemplazos específicos
replacements = [
    ('Panel del DueÃ±o', 'Panel del Dueño'),
    ('href="dueÃ±o.css"', 'href="dueño.css"'),
]

original_content = content
for old, new in replacements:
    if old in content:
        content = content.replace(old, new)
        print(f"✅ Reemplazado: {old} → {new}")
    else:
        print(f"⚠️  NO ENCONTRADO: {old}")

if content != original_content:
    # Guardar el archivo
    with open('dueño.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("\n✅ Archivo guardado correctamente")
else:
    print("\n⚠️  No se realizaron cambios")
