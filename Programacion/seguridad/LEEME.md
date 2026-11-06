# Seguridad de SiGeRU (Ciberseguridad, 3ra entrega)

| Carpeta / archivo | Qué es |
|-------------------|--------|
| `ssl/crear_certificados.sh` | Crea la autoridad certificadora **EcoSync CA** y el certificado SSL del servidor, firmado por la CA (RSA + SHA-256) |
| `ssl/sigeru-ssl.conf` | Sitio de Apache con HTTPS: solo TLS 1.2 y 1.3, cifrados fuertes, HSTS y redirección de HTTP a HTTPS |
| `respaldos/crear_claves.sh` | Crea las claves de los respaldos: RSA 3072 para firmar y una contraseña al azar para cifrar |
| `respaldos/respaldo_firmado.sh` | Respaldo de la base con **hash SHA-256**, **cifrado AES-256** y **firma digital RSA** |
| `respaldos/verificar_respaldo.sh` | Comprueba la firma, descifra, comprueba el hash y (si se pide) restaura |
| `owasp/revision_owasp.sh` | Revisión automática contra el OWASP Top 10: 28 pruebas que atacan al sistema de verdad |

Además, dentro de las aplicaciones:

- `sigeru-api-usuarios/cifrado.php`: cifra el teléfono de los usuarios con **AES-256-GCM** y calcula el resumen SHA-256 del código de verificación.
- Las contraseñas se guardan con **bcrypt** (`password_hash`).

## HTTPS en el servidor (Ubuntu, MV1)

```bash
sudo bash seguridad/ssl/crear_certificados.sh sigeru.local 192.168.1.200
sudo cp seguridad/ssl/sigeru-ssl.conf /etc/apache2/sites-available/
sudo a2enmod ssl headers rewrite
sudo a2ensite sigeru-ssl
sudo systemctl reload apache2
```

El archivo `/etc/ssl/sigeru/ca.crt` se instala en las computadoras y celulares que usan SiGeRU para que el navegador
confíe en el certificado (en Windows: doble clic → Instalar certificado → Equipo local → "Entidades de certificación
raíz de confianza").

## HTTPS en XAMPP (Windows)

XAMPP ya trae el módulo SSL prendido en el puerto 443, con un certificado de ejemplo.

1. Abrir **Git Bash** en la carpeta del proyecto y correr:
   `bash seguridad/ssl/crear_certificados.sh localhost 127.0.0.1 /c/xampp/apache/conf/sigeru`
2. En `C:\xampp\apache\conf\extra\httpd-ssl.conf` cambiar estas dos líneas:
   ```
   SSLCertificateFile "conf/sigeru/sigeru.crt"
   SSLCertificateKeyFile "conf/sigeru/sigeru.key"
   ```
3. Reiniciar Apache desde el panel de XAMPP.
4. Instalar `C:\xampp\apache\conf\sigeru\ca.crt` como se explica arriba y entrar a https://localhost/sigeru-landing/

## Respaldos firmados (MV2)

```bash
sudo bash seguridad/respaldos/crear_claves.sh                 # una sola vez
sudo cp seguridad/respaldos/*.sh /opt/sigeru/
sudo crontab -e    →    30 2 * * * /opt/sigeru/respaldo_firmado.sh
```

Para restaurar: `sudo bash /opt/sigeru/verificar_respaldo.sh sigeru_FECHA.sql.gz.enc restaurar`.
Si alguien cambió aunque sea un byte del respaldo, la firma no da y no se restaura.

## Revisión OWASP

Con los datos de prueba cargados: `bash seguridad/owasp/revision_owasp.sh` (o `https://sigeru.local` como parámetro).
Al final dice cuántas pruebas pasaron.

## Claves que NO se suben a GitHub

| Archivo | Dónde |
|---------|-------|
| Clave de cifrado de datos personales | `sigeru_clave.php`, una carpeta arriba del proyecto (se crea sola). **Si se pasa la base a otra PC, hay que copiarla también** |
| Contraseña de Gmail | `sigeru_correo.php`, una carpeta arriba del proyecto |
| Claves de los respaldos | `/etc/sigeru` en MV2 (solo root) |
| Clave privada del certificado y de la CA | `/etc/ssl/sigeru` (solo root) |
