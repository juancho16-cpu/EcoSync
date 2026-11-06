// Idioma del sistema. El administrador lo elige en Parametros (es = español, en = ingles).
// Las paginas estan escritas en español; si el idioma es ingles, se cambian los textos
// que aparecen en esta lista por su traduccion.

const TRADUCCIONES = {
    en: {
        // Menu
        "Inicio": "Home", "Mapa": "Map", "Incidencias": "Incidents", "Contenedores": "Containers",
        "Camiones": "Trucks", "Recorridos": "Routes", "Centros de acopio": "Collection centers",
        "Maquinaria": "Machinery", "Repuestos": "Spare parts", "Informes": "Reports",
        "Usuarios": "Users", "Parámetros": "Settings", "Seguridad": "Security", "Cerrar sesión": "Log out",
        "Notificaciones": "Notifications", "Marcar todas como leídas": "Mark all as read",
        // Titulos
        "Hola,": "Hello,", "Este es el resumen del sistema.": "This is the system summary.",
        "Contenedores": "Containers", "Camiones": "Trucks", "Recorridos": "Routes", "Centros de acopio": "Collection centers",
        "Maquinaria": "Machinery", "Repuestos": "Spare parts", "Informes": "Reports", "Usuarios": "Users",
        "Incidencias": "Incidents", "Parámetros": "Settings", "Mapa de Montevideo": "Montevideo map",
        "Alertas": "Alerts", "Estadísticas": "Statistics", "Accesos rápidos": "Shortcuts",
        "Top de contenedores desbordados del mes": "Most overflowing containers this month",
        "Incidencias sin resolver por municipio": "Unresolved incidents by municipality",
        "Incidencias del mes por tipo": "Incidents this month by type",
        "Problemas en los contenedores y su seguimiento: Abierta → En proceso → Resuelta.": "Container problems and their follow-up: Open → In progress → Resolved.",
        // Tarjetas del panel
        "Contenedores rotos o desbordados": "Broken or overflowing containers", "Incidencias abiertas": "Open incidents",
        "Incidencias en proceso": "Incidents in progress", "Resueltas en el mes": "Resolved this month",
        "Tiempo promedio de resolución": "Average resolution time", "Mes": "Month",
        "Incidencias en total": "Total incidents", "Abiertas (sin resolver)": "Open (unresolved)", "Cerradas (resueltas)": "Closed (resolved)",
        "Avisar un problema": "Report a problem", 
        // Botones
        "Nuevo contenedor": "New container", "Nuevo camión": "New truck", "Asignar recorrido": "Assign route",
        "Nuevo centro": "New center", "Nueva máquina": "New machine", "Nuevo repuesto": "New spare part",
        "Nuevo usuario": "New user", "Reportar incidencia": "Report incident", "Estoy acá": "I'm here", "Agregar contenedor": "Add container",
        "Incidencias (Excel)": "Incidents (Excel)", "Recolecciones (Excel)": "Collections (Excel)", "Imprimir": "Print",
        "Alertas": "Alerts", "Estadísticas del mes": "Statistics of the month", "¿A dónde querés ir?": "Where do you want to go?",
        "Los contenedores que más se llenaron este mes": "Containers that overflowed the most this month",
        "Problemas sin resolver en cada municipio": "Unresolved problems by municipality",
        "Qué tipo de problemas hubo este mes": "Types of problems this month", "Entrar": "Open",
        "Mapa de Montevideo": "Montevideo map", "Recorridos de hoy": "Today's routes", "Cómo está cada municipio": "Status by municipality",
        "Cuánta basura se juntó": "Waste collected", "Kilos por municipio": "Kilos by municipality", "Kilos por tipo de residuo": "Kilos by waste type",
        "Reportar un problema": "Report a problem", "Contenedores rotos o desbordados": "Broken or overflowing containers", "Guardar": "Save", "Cancelar": "Cancel",
        "Cerrar": "Close", "Ver informe": "View report", "Imprimir": "Print", "Exportar CSV": "Export CSV",
        "Editar": "Edit", "Borrar": "Delete", "Seguimiento": "Follow-up", "Historial": "History", "Enviar aviso": "Send report",
        "Agregar contenedor": "Add container", "Estoy acá": "I'm here",
        // Columnas de las tablas
        "ID": "ID", "Calle": "Street", "Zona": "Area", "Municipio": "Municipality", "Tipo de residuo": "Waste type",
        "Capacidad": "Capacity", "Estado": "Status", "Acciones": "Actions", "Matrícula": "Plate", "Disponibilidad": "Availability",
        "Mantenimientos": "Maintenance", "Fecha": "Date", "Ruta": "Route", "Cuadrilla": "Crew", "Camión": "Truck",
        "Horario": "Schedule", "Zonas": "Areas", "Nombre": "Name", "Ubicación": "Location", "Capacidad usada": "Capacity used",
        "Residuos que recibe": "Waste received", "Operario": "Operator", "Tipo": "Type", "Instalación": "Facility",
        "Correo": "Email", "Teléfono": "Phone", "Rol": "Role", "Detectada": "Detected", "Contenedor": "Container",
        "Prioridad": "Priority", "Resuelta": "Resolved", "Días": "Days", "Residuo": "Waste", "Dónde está": "Location",
        "Cantidad": "Quantity", "Mínimo": "Minimum", "Descripción": "Description", "Costo": "Cost", "Kilos": "Kilos",
        "Recolecciones": "Collections", "Parámetro": "Setting", "Valor": "Value",
        // Filtros y formularios
        "Desde": "From", "Hasta": "To", "Todos los estados": "All statuses", "Abiertas": "Open", "En proceso": "In progress",
        "Resueltas": "Resolved", "Abiertas y cerradas": "Open and closed", "Solo abiertas (sin resolver)": "Open only (unresolved)",
        "Solo cerradas (resueltas)": "Closed only (resolved)", "Todos los municipios": "All municipalities",
        "Todos los residuos": "All waste types", "Sin asignar": "Unassigned", "Cargando...": "Loading...",
        "Buscar por calle, zona, municipio, estado o residuo...": "Search by street, area, municipality, status or waste...",
        "Buscar por calle, zona, municipio, tipo o cuadrilla...": "Search by street, area, municipality, type or crew...",
        "Buscar por matrícula o estado...": "Search by plate or status...",
        "Buscar por nombre, calle o zona...": "Search by name, street or area...",
        "Buscar por nombre, correo, rol o estado...": "Search by name, email, role or status...",
        "Buscar por nombre, tipo, estado o instalación...": "Search by name, type, status or facility...",
        "Buscar por nombre, tipo, residuo o instalación...": "Search by name, type, waste or facility...",
        "Volumen recolectado": "Volume collected", "Kilos recolectados": "Kilos collected",
        "Vaciados de contenedores": "Container emptyings", "Promedio por vaciado": "Average per emptying",
        "Días entre recolecciones": "Days between collections"
    }
};

let idiomaActual = "es";

// Cambia los textos de la pagina al idioma elegido
function traducirPagina(idioma) {
    idiomaActual = idioma;
    document.documentElement.lang = idioma;
    if (idioma == "es" || TRADUCCIONES[idioma] == undefined) {
        return;
    }
    const diccionario = TRADUCCIONES[idioma];

    // Revisamos los elementos que solo tienen texto adentro
    const elementos = document.querySelectorAll("a, button, th, label, h1, h2, h3, h4, p, span, strong, option");
    for (const elemento of elementos) {
        // Las opciones sin "value" usan el texto como valor: no se pueden traducir
        if (elemento.tagName == "OPTION" && !elemento.hasAttribute("value")) continue;

        for (const nodo of elemento.childNodes) {
            // Solo cambiamos los pedazos de texto (no las etiquetas que tenga adentro)
            if (nodo.nodeType == Node.TEXT_NODE) {
                const texto = nodo.textContent.trim();
                if (diccionario[texto] != undefined) {
                    nodo.textContent = nodo.textContent.replace(texto, diccionario[texto]);
                }
            }
        }
    }

    // Los textos de ayuda de los buscadores
    const campos = document.querySelectorAll("input[placeholder]");
    for (const campo of campos) {
        if (diccionario[campo.placeholder] != undefined) {
            campo.placeholder = diccionario[campo.placeholder];
        }
    }
}

// Traduce un texto suelto (para los que se arman con JavaScript)
function traducir(texto) {
    if (idiomaActual == "es" || TRADUCCIONES[idiomaActual] == undefined) {
        return texto;
    }
    return TRADUCCIONES[idiomaActual][texto] ?? texto;
}
