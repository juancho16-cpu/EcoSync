#!/bin/bash
# =====================================================================
# Crea los certificados SSL de SiGeRU (para HTTPS)
#
# 1. Una autoridad certificadora propia: "EcoSync CA" (clave RSA 4096 + certificado autofirmado, 10 años).
# 2. El certificado del servidor (clave RSA 2048), FIRMADO DIGITALMENTE por la CA con SHA-256, 825 dias.
#    Sirve para el nombre que se pase como parametro y para la IP del servidor (192.168.1.200).
#
# Uso:   sudo bash crear_certificados.sh sigeru.local 192.168.1.200
# Queda: /etc/ssl/sigeru/ca.crt  (se instala en las PCs y celulares de la Intendencia para que confien)
#        /etc/ssl/sigeru/sigeru.crt y sigeru.key (los usa Apache)
#
# Si el servidor tiene un dominio publico, en vez de esto se usa Let's Encrypt:
#        sudo apt install certbot python3-certbot-apache && sudo certbot --apache -d sigeru.ejemplo.uy
# =====================================================================

NOMBRE=${1:-sigeru.local}
IP=${2:-192.168.1.200}
CARPETA=${3:-/etc/ssl/sigeru}

mkdir -p "$CARPETA"
cd "$CARPETA" || exit 1

# 1. Autoridad certificadora (solo si no existe)
if [ ! -f ca.key ]; then
    openssl genrsa -out ca.key 4096 2>/dev/null
    openssl req -x509 -new -key ca.key -sha256 -days 3650 -out ca.crt \
        -subj "/C=UY/ST=Montevideo/O=EcoSync/CN=EcoSync CA"
fi

# 2. Clave del servidor y pedido de firma (CSR)
openssl genrsa -out sigeru.key 2048 2>/dev/null
openssl req -new -key sigeru.key -out sigeru.csr \
    -subj "/C=UY/ST=Montevideo/O=EcoSync/CN=$NOMBRE"

# Nombres y usos permitidos del certificado
cat > sigeru.ext <<FIN
basicConstraints = CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = DNS:$NOMBRE, DNS:localhost, IP:$IP, IP:127.0.0.1
FIN

# 3. La CA firma el certificado del servidor
openssl x509 -req -in sigeru.csr -CA ca.crt -CAkey ca.key -CAcreateserial \
    -out sigeru.crt -days 825 -sha256 -extfile sigeru.ext

# Las claves privadas solo las puede leer root
chmod 600 ca.key sigeru.key
chmod 644 ca.crt sigeru.crt

echo ""
echo "Comprobando la firma del certificado con la CA:"
openssl verify -CAfile ca.crt sigeru.crt
