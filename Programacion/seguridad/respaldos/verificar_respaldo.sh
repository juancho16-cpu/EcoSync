#!/bin/bash

CLAVES=${CLAVES:-/etc/sigeru}
ARCHIVO="$1"
NOMBRE="${ARCHIVO%.enc}"

if [ -z "$ARCHIVO" ] || [ ! -f "$ARCHIVO" ]; then
    echo "Uso: bash verificar_respaldo.sh archivo.sql.gz.enc [restaurar]"
    exit 1
fi

echo "1. Firma digital:"
if ! openssl dgst -sha256 -verify "$CLAVES/firma_publica.pem" -signature "$ARCHIVO.firma" "$ARCHIVO"; then
    echo "   La firma NO es valida: el respaldo fue modificado o no es nuestro. No se usa."
    exit 2
fi

echo "2. Descifrando..."
if ! openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in "$ARCHIVO" -out "$NOMBRE" -pass file:"$CLAVES/clave_respaldo"; then
    echo "   No se pudo descifrar (clave equivocada)."
    exit 3
fi

echo "3. Hash SHA-256:"
if ! sha256sum -c "$NOMBRE.sha256"; then
    echo "   El hash no coincide: el respaldo esta incompleto."
    exit 4
fi

if [ "$2" == "restaurar" ]; then
    echo "4. Restaurando en MySQL..."
    gunzip -c "$NOMBRE" | mysql
    echo "   Listo."
fi
