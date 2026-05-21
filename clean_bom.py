#!/usr/bin/env python3
import os
import glob

# Buscar todos los archivos PHP
php_files = glob.glob("*.php")

bom_files = []
cleaned_files = []

for filename in php_files:
    filepath = os.path.join(".", filename)
    
    try:
        # Leer el archivo en bytes
        with open(filepath, 'rb') as f:
            content = f.read()
        
        # Verificar si tiene BOM UTF-8
        if content.startswith(b'\xef\xbb\xbf'):
            bom_files.append(filename)
            
            # Remover BOM
            content_sin_bom = content[3:]
            
            # Guardar sin BOM
            with open(filepath, 'wb') as f:
                f.write(content_sin_bom)
            
            cleaned_files.append(filename)
            print(f"✅ LIMPIADO: {filename}")
        else:
            print(f"✓ OK: {filename}")
    
    except Exception as e:
        print(f"❌ ERROR en {filename}: {e}")

print(f"\n📊 Resumen:")
print(f"Archivos con BOM encontrados: {len(bom_files)}")
print(f"Archivos limpiados: {len(cleaned_files)}")

if bom_files:
    print(f"\nArchivos que tenían BOM:")
    for f in bom_files:
        print(f"  - {f}")
