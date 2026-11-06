# SiGeRU - Frontend de Usuarios

Interfaz para los usuarios del Sistema de Gestión de Residuos Urbanos (SiGeRU), desarrollado por **EcoSync**.

Permite iniciar sesión y, según el rol, ver, agregar, modificar y borrar usuarios, contenedores,
camiones (con sus mantenimientos), recorridos, centros de acopio y maquinaria.
También tiene el panel de control con estadísticas y alertas, el seguimiento de incidencias y los informes.
No guarda datos ni accede a la base de datos: todo se hace a través de las APIs.

## Tecnologías

HTML5, CSS y JavaScript.

## Qué puede hacer cada rol

"Todo" = ver, agregar, modificar y borrar.

| Rol           | Contenedores | Camiones y mantenimientos | Recorridos   | Centros de acopio | Maquinaria | Usuarios |
|---------------|--------------|---------------------------|--------------|-------------------|------------|----------|
| Administrador | Todo         | Todo                      | Todo         | Todo              | Todo       | Todo     |
| Operario      | Todo         | Todo                      | Todo         | Todo              | Todo       | -        |
| Recolector    | Ver          | Ver                       | Ver los suyos| Ver               | Ver        | -        |
| Vecino        | Ver          | -                         | -            | -                 | -          | -        |

El menú y los botones se muestran según el rol, y además cada API revisa el rol antes de responder.

## Notificaciones (campana )

Todas las páginas tienen la campana arriba del menú: muestra cuántas notificaciones no se leyeron y se
actualiza sola cada 15 segundos. Cuando llega una nueva aparece un cartel abajo a la derecha.
Al tocar una notificación se abre la página que corresponde (por ejemplo Incidencias).

## Repuestos (repuestos.html)

Inventario de contenedores de repuesto, tapas y ruedas. Los botones **−** y **+** usan o agregan una unidad.
Los que están en la cantidad mínima o menos se marcan en rojo y generan una alerta.

## Parámetros (parametros.html, solo administrador)

Valores del sistema que se cambian sin tocar el código: días para avisar de un desborde, porcentaje de
centro lleno, si hay que aprobar los registros, **idioma** (español o inglés) y kilos por litro.
Con el idioma en inglés se traducen el menú, los títulos, las tablas y los botones (`js/idioma.js`).

## Incidencias para el vecino

El vecino entra a **Incidencias** y la página se llama **Mis incidencias**. Ahí reporta una incidencia
eligiendo el contenedor, qué pasa (está desbordado, está roto, no lo vinieron a vaciar o hay basura en la calle),
**cuenta con detalle qué pasó** y **dónde exactamente** (por ejemplo "frente al 2510, al lado de la parada").
Después ve el estado de cada una. El personal ve en cada incidencia qué vecino la reportó.
Un vecino no puede reportar dos veces el mismo contenedor mientras no se resuelva.

## Usuarios (usuarios.html)

Además del CRUD, muestra el estado de cada usuario. Los vecinos que se registraron y verificaron su correo
quedan **Pendiente** y el administrador los aprueba o rechaza con los botones y .
Con el botón **Rol** el administrador le cambia el rol a cualquier usuario (por ejemplo, hacer Operario o
Administrador a otra persona). No se puede cambiar su propio rol.

## Panel de control (panel.html)

- **Alertas** para cada rol. El número de alertas se ve en el menú, al lado de Inicio.
- **Estadísticas** del mes elegido (no las ve el vecino): incidencias abiertas y en proceso, resueltas en el mes,
  tiempo promedio de resolución, top de contenedores desbordados, incidencias sin resolver por municipio y por tipo.

## Incidencias (incidencias.html)

Registro y seguimiento de incidencias (no lo ve el vecino). Se filtra por estado y se busca por calle, zona,
municipio, tipo o cuadrilla. El botón **Seguimiento** cambia el estado (Abierta → En proceso → Resuelta),
la prioridad y la cuadrilla; el recolector solo cambia el estado de las de su cuadrilla.
El botón **Historial** muestra todas las incidencias que tuvo ese contenedor.

## Informes (informes.html)

Listado de incidencias abiertas y cerradas entre dos fechas, con el total, cuántas siguen abiertas,
cuántas se cerraron y el tiempo promedio de resolución. También el **volumen recolectado** (kilos por
municipio y por tipo de residuo, y cada cuántos días se vacía un contenedor; datos simulados).
El botón **Imprimir** lo deja listo para imprimir o guardar como PDF, y los botones **Exportar** bajan
las incidencias y las recolecciones en un archivo CSV que se abre con Excel.

## Recorridos (recorridos.html)

Sirve para asignar una ruta a una cuadrilla, con el camión, la fecha, el horario y las zonas que recorre.
Solo se puede elegir un camión que esté **Operativo** y **Disponible** (RNE06); si no, la API devuelve el error.
En la página de camiones, el botón **Ver** muestra los mantenimientos de cada camión.

## Mapa interactivo (mapa.html)

Mapa de Montevideo hecho con [Leaflet](https://leafletjs.com) (guardado en `js/libs/leaflet`) y las imágenes de OpenStreetMap.

| Función | Quién la ve |
|---------|-------------|
| Contenedores con colores según su estado (verde, naranja, rojo) | Todos |
| Filtros por estado, tipo de residuo y municipio | Todos |
| Estoy acá: muestra tu ubicación y el contenedor más cercano | Todos |
| Incidencias abiertas, según su prioridad | Administrador, Operario, Recolector |
| Centros de acopio y vertederos | Administrador, Operario, Recolector |
| Camiones: los que están "En ruta" recorren su recorrido por las calles (simulación); los demás quedan quietos | Administrador, Operario, Recolector |
| Recorridos: contenedores numerados en orden y la línea del recorrido | Administrador y Operario ven todos, el Recolector ve el de su cuadrilla |
| Botón "Vaciado" en cada contenedor | Administrador, Operario, Recolector |
| Botón "Reportar incidencia" en cada contenedor (abre el formulario de Incidencias con ese contenedor) | Todos |
| Zonas críticas: municipios pintados según sus incidencias abiertas, y resumen por municipio | Administrador, Operario, Recolector |
| Agregar un contenedor tocando el mapa (el municipio se elige solo) | Administrador y Operario |

Las incidencias se reportan siempre con el mismo formulario (en Incidencias), lo haga un vecino o el personal.

**Movimiento de los camiones:** el camino por las calles se pide al servicio gratis de OSRM (mapas de
OpenStreetMap), pasando por los contenedores del recorrido y volviendo al principio. Si no responde, el camión
no anda en línea recta (podría cruzar el agua): se queda unos segundos en cada contenedor y pasa al siguiente,
y la línea del recorrido se dibuja punteada.

## Diseño

- Iconos SVG de [Lucide](https://lucide.dev) (licencia ISC), todos juntos en `img/iconos.svg`. En el HTML se usan con
  `<svg class="icono"><use href="img/iconos.svg#mapa"></use></svg>` y en JavaScript con la función `icono("mapa")`.
- Letra Inter (Google Fonts). Si no hay internet se usa la letra del sistema.
- Animaciones hechas solo con CSS: las páginas entran subiendo, las tarjetas se levantan al pasar el mouse,
  las barras de los gráficos crecen y los números del inicio cuentan desde 0. Si la computadora tiene activado
  "reducir movimiento", se sacan.

- En el celular la tabla de Usuarios se ve como tarjetas, con los botones de Aprobar y Rechazar
  grandes, para poder aceptar registros desde el teléfono.

### Límites de los municipios

Para que las zonas críticas pinten los límites reales de cada municipio, hay que guardar el archivo
de límites de la Intendencia de Montevideo en `js/datos/municipios.json` (formato GeoJSON).
Si el archivo no está, el mapa funciona igual y marca cada municipio con un círculo en su centro.

## Botones sin onclick (política CSP)

Los botones no tienen `onclick` en el HTML. En su lugar dicen qué función llamar con atributos `data-`,
y `js/vistas/eventos.js` escucha los clics de toda la página y llama a esa función:

```html
<button data-click="nuevoUsuario">Nuevo usuario</button>                 <!-- nuevoUsuario() -->
<button data-click="aprobarElegido" data-args="5,true">Aprobar</button>  <!-- aprobarElegido(5, true) -->
<select data-change="cargarIncidencias">...</select>                     <!-- al cambiar -->
<input data-input="buscar">                                               <!-- al escribir -->
```

Así se pudo agregar la cabecera `Content-Security-Policy` en `.htaccess`: el navegador solo ejecuta
los `.js` del sistema, y si alguien logra meter un `<script>` o un `onclick` en una página no se ejecuta.
Desde afuera solo se permite la letra de Google Fonts, el mapa de OpenStreetMap y las rutas de OSRM.

## Estructura (MVC)

```
index.html                      Inicio de sesión
panel.html                      Inicio del panel
mapa.html                       Mapa interactivo de Montevideo
incidencias.html                Registro y seguimiento de incidencias
informes.html                   Informes de incidencias y volumen recolectado (imprimir y exportar)
repuestos.html                  Inventario de repuestos
parametros.html                 Parámetros del sistema e idioma (administrador)
usuarios.html                   Usuarios y aprobación de registros
contenedores.html               Contenedores
camiones.html                   Camiones y sus mantenimientos
recorridos.html                 Recorridos (rutas asignadas a cuadrillas)
centros.html                    Centros de acopio
maquinaria.html                 Maquinaria
css/styles.css
js/config.js                    Direcciones de las APIs
js/idioma.js                    Traducciones (idioma parametrizable)
js/modelos/                     Pedidos a las APIs (servidor, usuarios, gestion, recoleccion)
js/vistas/                      Funciones que muestran datos en pantalla (comun, tablas, mapa, panel, notificaciones, eventos)
js/libs/leaflet/                Libreria del mapa (Leaflet 1.9.4)
js/datos/                       Límites de los municipios (municipios.json, opcional)
js/controladores/               Lógica de cada página
```

## Cómo ejecutarlo (XAMPP)

1. Copiar la carpeta `sigeru-frontend` dentro de `C:\xampp\htdocs\`.
2. Prender **Apache** y **MySQL** en el panel de XAMPP (la base de datos tiene que estar creada).
3. Abrir http://localhost/sigeru-frontend/

Necesita la API de Usuarios, la de Gestión y la de Recolección en `htdocs/sigeru-api-usuarios`,
`htdocs/sigeru-api-gestion` y `htdocs/sigeru-api-recoleccion`.
Las direcciones se configuran en `js/config.js`.

Usuario de prueba: **admin@sigeru.uy** / **admin123**

