#!/bin/bash

NOMBRE=${1:-sigeru.local}
IP=${2:-192.168.1.200}
CARPETA=${3:-/etc/ssl/sigeru}

mkdir -p "$CARPETA"
cd "$CARPETA" || exit 1

if [ ! -f ca.key ]; then
    openssl genrsa -out ca.key 4096 2>/dev/null
    openssl req -x509 -new -key ca.key -sha256 -days 3650 -out ca.crt \
        -subj "/C=UY/ST=Montevideo/O=EcoSync/CN=EcoSync CA"
fi

openssl genrsa -out sigeru.key 2048 2>/dev/null
openssl req -new -key sigeru.key -out sigeru.csr \
    -subj "/C=UY/ST=Montevideo/O=EcoSync/CN=$NOMBRE"

cat > sigeru.ext <<FIN
basicConstraints = CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = DNS:$NOMBRE, DNS:localhost, IP:$IP, IP:127.0.0.1
FIN

openssl x509 -req -in sigeru.csr -CA ca.crt -CAkey ca.key -CAcreateserial \
    -out sigeru.crt -days 825 -sha256 -extfile sigeru.ext

chmod 600 ca.key sigeru.key
chmod 644 ca.crt sigeru.crt

echo ""
echo "Comprobando la firma del certificado con la CA:"
openssl verify -CAfile ca.crt sigeru.crt
