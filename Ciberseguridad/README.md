# Ciberseguridad – EcoSync

Documento de la 3ra entrega: **[EcoSync_Ciberseguridad_3ra_entrega.pdf](EcoSync_Ciberseguridad_3ra_entrega.pdf)** (22 páginas).
Requisitos de Ciberseguridad 2026, Prof. Gabriela Borba.

## Dónde está cada cosa en el código

Todo lo implementado está en la carpeta [`Programacion/`](../Programacion).

| Requisito | Qué se hizo | Dónde está |
| --- | --- | --- |
| Hashing, cifrado y firma digital | bcrypt para contraseñas, SHA-256 del código de verificación, AES-256-GCM del teléfono, respaldos con SHA-256 + AES-256 + firma RSA | `Programacion/sigeru-api-usuarios/cifrado.php`, `Programacion/sigeru-api-usuarios/modelos/Usuario.php`, `Programacion/seguridad/respaldos/` |
| Certificado SSL en el servidor | CA propia "EcoSync CA", Apache solo con TLS 1.2 y 1.3, HSTS | `Programacion/seguridad/ssl/` |
| Políticas de seguridad de usuario | Contraseñas de 8+ caracteres con letras y números, bloqueo a los 5 intentos, sesión de 30 minutos, roles, auditoría (página Seguridad) | APIs (`seguridad.php`, `config.php`) y `Programacion/sigeru-frontend/seguridad.html` |
| Informe OWASP | OWASP Top 10 2021, 28 pruebas automáticas | `Programacion/seguridad/owasp/revision_owasp.sh` |

## Cambios después del informe (06/11/2026)

Se aplicaron las dos recomendaciones de la sección 5.3 del informe:

- **CSP (A05):** los botones ya no usan `onclick` en el HTML (ahora `data-click` + `js/vistas/eventos.js`) y el frontend y la landing mandan la cabecera `Content-Security-Policy`.
- **Librerías (A06):** PHPMailer se actualizó de la 6.9.1 a la 6.12.0.

Pasos para instalar el certificado y la seguridad en XAMPP: `Programacion/seguridad/LEEME.md`.
