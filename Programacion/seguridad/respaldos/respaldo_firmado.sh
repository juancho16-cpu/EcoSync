#!/bin/bash

CLAVES=${CLAVES:-/etc/sigeru}
CARPETA=${CARPETA:-/var/backups/sigeru}
DESTINO=${DESTINO:-respaldos@192.168.1.202:/srv/respaldos/sigeru}
FECHA=$(date +%Y-%m-%d_%H%M)
NOMBRE="sigeru_$FECHA.sql.gz"

mkdir -p "$CARPETA"
cd "$CARPETA" || exit 1

mysqldump --single-transaction --routines --databases sigeru | gzip > "$NOMBRE"

sha256sum "$NOMBRE" > "$NOMBRE.sha256"

openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt \
    -in "$NOMBRE" -out "$NOMBRE.enc" -pass file:"$CLAVES/clave_respaldo"
rm -f "$NOMBRE"

openssl dgst -sha256 -sign "$CLAVES/firma_privada.pem" -out "$NOMBRE.enc.firma" "$NOMBRE.enc"

chmod 600 "$NOMBRE".*
echo "$(date '+%F %T') respaldo $NOMBRE.enc creado y firmado" >> "$CARPETA/respaldos.log"

if [ "$DESTINO" != "no" ]; then
    scp -q "$NOMBRE.enc" "$NOMBRE.enc.firma" "$NOMBRE.sha256" "$DESTINO/" \
        && echo "$(date '+%F %T') copiado a $DESTINO" >> "$CARPETA/respaldos.log"
fi
find "$CARPETA" -name "sigeru_*" -mtime +30 -delete
