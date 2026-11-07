# SiGeRU - Landing Page

Página principal del Sistema de Gestión de Residuos Urbanos (SiGeRU), desarrollado por **EcoSync**.

Brinda información al público y recibe las solicitudes de registro de usuarios (vecinos).
Al registrarse le llega un correo para verificar la cuenta.
No guarda datos: el registro se envía a la **API de Usuarios**.

## Tecnologías

HTML5, CSS y JavaScript.

## Estructura (MVC)

```
index.html                 Vista principal
css/styles.css
js/config.js               Direcciones de la API y del frontend
js/modelos/registro.js     Modelo: envía el registro a la API de Usuarios
js/vistas/mensajes.js      Vista: muestra mensajes en pantalla
js/controladores/registro.js  Controlador: valida y maneja el formulario
verificar.html             Página que se abre desde el enlace del correo de verificación
js/controladores/verificar.js Controlador: manda el código del enlace a la API y muestra "¡Te registraste correctamente!"
```

## Cómo ejecutarla (XAMPP)

1. Copiar la carpeta `sigeru-landing` dentro de `C:\xampp\htdocs\`.
2. Prender **Apache** en el panel de XAMPP.
3. Abrir http://localhost/sigeru-landing/

Necesita la API de Usuarios en `htdocs/sigeru-api-usuarios`.
Las direcciones se configuran en `js/config.js`.

