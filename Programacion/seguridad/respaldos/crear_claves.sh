#!/bin/bash
# =====================================================================
# Crea las claves que usan los respaldos firmados (se corre UNA vez en el servidor de BD, MV2)
#
#   firma_privada.pem  clave RSA 3072 para FIRMAR los respaldos (solo la tiene MV2)
#   firma_publica.pem  clave publica para VERIFICAR la firma (se copia a MV3 y a quien restaure)
#   clave_respaldo     contraseña al azar para CIFRAR los respaldos con AES-256
#
# Uso: sudo bash crear_claves.sh   (quedan en /etc/sigeru, solo root las puede leer)
# =====================================================================

CARPETA=${1:-/etc/sigeru}
mkdir -p "$CARPETA"
cd "$CARPETA" || exit 1

if [ -f firma_privada.pem ]; then
    echo "Las claves ya existen en $CARPETA, no se pisan."
    exit 0
fi

openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out firma_privada.pem 2>/dev/null
openssl pkey -in firma_privada.pem -pubout -out firma_publica.pem
openssl rand -base64 48 > clave_respaldo

chmod 600 firma_privada.pem clave_respaldo
chmod 644 firma_publica.pem
echo "Claves creadas en $CARPETA. Guardar una copia de clave_respaldo en un lugar seguro: sin ella los respaldos no se pueden abrir."
