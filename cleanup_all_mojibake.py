#!/usr/bin/env python3
import glob
import os

# Archivos PHP a revisar
php_files = glob.glob("*.php")

mojibake_pairs = [
    ('dueÃ±o', 'dueño'),
    ('aÃ±o', 'año'),
    ('nÃºmero', 'número'),
    ('ValidaciÃ³n', 'Validación'),
    ('diseÃ±o', 'diseño'),
    ('seÃ±al', 'señal'),
]

for filename in php_files:
    try:
        with open(filename, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        changes = []
        
        for mojibake, correct in mojibake_pairs:
            if mojibake in content:
                count = content.count(mojibake)
                content = content.replace(mojibake, correct)
                changes.append(f"    {mojibake} → {correct} ({count}x)")
        
        if content != original:
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✅ {filename}")
            for change in changes:
                print(change)
        
    except Exception as e:
        print(f"❌ ERROR en {filename}: {e}")

print("\n✅ Limpieza completada")
