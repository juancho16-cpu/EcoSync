#!/bin/bash
# =====================================================================
# Respaldo de la base de SiGeRU con hashing, cifrado y firma digital
#
# 1. mysqldump de la base sigeru, comprimido con gzip
# 2. HASH:   resumen SHA-256 del respaldo (para comprobar que llega entero)
# 3. CIFRADO: AES-256-CBC con una clave derivada con PBKDF2 (200.000 vueltas) desde clave_respaldo
# 4. FIRMA:  firma digital RSA-SHA256 del archivo cifrado con firma_privada.pem
# 5. Copia los archivos al servidor de respaldo (MV3) por SSH y borra los de mas de 30 dias
#
# Uso (cron todas las noches a las 2:30):   30 2 * * * /opt/sigeru/respaldo_firmado.sh
# =====================================================================

CLAVES=${CLAVES:-/etc/sigeru}
CARPETA=${CARPETA:-/var/backups/sigeru}
DESTINO=${DESTINO:-respaldos@192.168.1.202:/srv/respaldos/sigeru}
FECHA=$(date +%Y-%m-%d_%H%M)
NOMBRE="sigeru_$FECHA.sql.gz"

mkdir -p "$CARPETA"
cd "$CARPETA" || exit 1

# 1. Respaldo comprimido (--single-transaction: copia consistente sin trancar las tablas)
mysqldump --single-transaction --routines --databases sigeru | gzip > "$NOMBRE"

# 2. Hash SHA-256 del respaldo sin cifrar
sha256sum "$NOMBRE" > "$NOMBRE.sha256"

# 3. Cifrado AES-256 (el archivo sin cifrar se borra)
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt \
    -in "$NOMBRE" -out "$NOMBRE.enc" -pass file:"$CLAVES/clave_respaldo"
rm -f "$NOMBRE"

# 4. Firma digital del archivo cifrado
openssl dgst -sha256 -sign "$CLAVES/firma_privada.pem" -out "$NOMBRE.enc.firma" "$NOMBRE.enc"

chmod 600 "$NOMBRE".*
echo "$(date '+%F %T') respaldo $NOMBRE.enc creado y firmado" >> "$CARPETA/respaldos.log"

# 5. Copia al servidor de respaldo y limpieza
if [ "$DESTINO" != "no" ]; then
    scp -q "$NOMBRE.enc" "$NOMBRE.enc.firma" "$NOMBRE.sha256" "$DESTINO/" \
        && echo "$(date '+%F %T') copiado a $DESTINO" >> "$CARPETA/respaldos.log"
fi
find "$CARPETA" -name "sigeru_*" -mtime +30 -delete
