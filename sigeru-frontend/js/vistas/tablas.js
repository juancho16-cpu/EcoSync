// Vista: arma las tablas de los listados

// Botones de Editar y Borrar de una fila. "tipo" es Contenedor, Camion, Usuario, Centro o Maquina,
// y se usa para llamar a la funcion que corresponde (por ejemplo editarCamion(3)).
function botonesAcciones(tipo, id) {
    return `<td class="acciones">
        <button class="btn btn-secondary btn-sm" data-click="editar${tipo}" data-args="${id}">${traducir("Editar")}</button>
        <button class="btn btn-danger btn-sm" data-click="borrar${tipo}Elegido" data-args="${id}">${traducir("Borrar")}</button>
    </td>`;
}

// Fila que se muestra cuando la lista esta vacia
function filaVacia(columnas, texto) {
    return `<tr><td colspan="${columnas}" class="vacio">${texto}</td></tr>`;
}

// conAcciones es true si el usuario puede editar y borrar
function mostrarTablaUsuarios(lista, conAcciones) {
    const tabla = document.getElementById("tablaUsuarios");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(8, "No hay usuarios");
        return;
    }

    // data-etiqueta es el nombre de la columna: en el celular la tabla se ve como tarjetas
    // y cada dato muestra su nombre al lado (ver .tabla-tarjetas en styles.css)
    let filas = "";
    for (const u of lista) {
        // Las solicitudes pendientes se marcan para que resalten en el celular
        let clase = "";
        if (u.estado == "Pendiente") {
            clase = "fila-pendiente";
        }
        filas += `<tr class="${clase}">
            <td data-etiqueta="ID">${u.id}</td>
            <td data-etiqueta="Nombre">${limpiarTexto(u.primer_nombre)} ${limpiarTexto(u.primer_apellido)}</td>
            <td data-etiqueta="Correo">${limpiarTexto(u.email)}</td>
            <td data-etiqueta="Teléfono">${limpiarTexto(u.telefono)}</td>
            <td data-etiqueta="Zona">${limpiarTexto(u.zona)}</td>
            <td data-etiqueta="Rol"><span class="etiqueta">${limpiarTexto(u.rol)}</span></td>
            <td data-etiqueta="Estado"><span class="etiqueta ${colorEstado(u.estado)}">${limpiarTexto(u.estado)}</span></td>`;
        if (conAcciones && u.estado == "Pendiente") {
            // Vecino que se registro y espera que lo aprueben
            filas += `<td class="acciones">
                <button class="btn btn-primary btn-sm" data-click="aprobarElegido" data-args="${u.id},true">${icono("check")}Aprobar</button>
                <button class="btn btn-danger btn-sm" data-click="aprobarElegido" data-args="${u.id},false">${icono("cerrar")}Rechazar</button>
            </td>`;
        } else if (conAcciones) {
            filas += `<td class="acciones">
                <button class="btn btn-secondary btn-sm" data-click="editarUsuario" data-args="${u.id}">${traducir("Editar")}</button>
                <button class="btn btn-secondary btn-sm" data-click="abrirCambioRol" data-args="${u.id}">${icono("llave")}${traducir("Rol")}</button>
                <button class="btn btn-danger btn-sm" data-click="borrarUsuarioElegido" data-args="${u.id}">${traducir("Borrar")}</button>
            </td>`;
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

function mostrarTablaContenedores(lista, conAcciones) {
    const tabla = document.getElementById("tablaContenedores");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(8, "No hay contenedores");
        return;
    }

    let filas = "";
    for (const c of lista) {
        filas += `<tr>
            <td>${c.id}</td>
            <td class="ajustar">${limpiarTexto(c.calle)}</td>
            <td>${limpiarTexto(c.zona)}</td>
            <td>${limpiarTexto(c.municipio)}</td>
            <td>${limpiarTexto(c.residuo)}</td>
            <td>${c.capacidad ? Number(c.capacidad) + " L" : ""}</td>
            <td><span class="etiqueta ${colorEstado(c.estado)}">${limpiarTexto(c.estado)}</span></td>`;
        if (conAcciones) {
            filas += botonesAcciones("Contenedor", c.id);
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

function mostrarTablaCamiones(lista, conAcciones) {
    const tabla = document.getElementById("tablaCamiones");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(7, "No hay camiones");
        return;
    }

    let filas = "";
    for (const c of lista) {
        filas += `<tr>
            <td>${c.id}</td>
            <td>${limpiarTexto(c.matricula)}</td>
            <td>${c.capacidad_carga ? Number(c.capacidad_carga) + " kg" : ""}</td>
            <td><span class="etiqueta ${colorEstado(c.estado)}">${limpiarTexto(c.estado)}</span></td>
            <td><span class="etiqueta ${colorEstado(c.disponibilidad)}">${limpiarTexto(c.disponibilidad)}</span></td>
            <td><button class="btn btn-secondary btn-sm" data-click="verMantenimientos" data-args="${c.id}">${icono("herramienta")}Ver</button></td>`;
        if (conAcciones) {
            filas += botonesAcciones("Camion", c.id);
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

function mostrarTablaCentros(lista, conAcciones) {
    const tabla = document.getElementById("tablaCentros");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(7, "No hay centros de acopio");
        return;
    }

    let filas = "";
    for (const c of lista) {
        // Nombres de los tipos de residuo que recibe, separados por coma
        let residuos = "";
        for (const r of c.residuos) {
            if (residuos != "") residuos += ", ";
            residuos += r.nombre;
        }

        // Capacidad usada, por ejemplo "3500 / 10000 kg"
        let capacidad = "";
        if (c.capacidad_actual || c.capacidad_max) {
            capacidad = Number(c.capacidad_actual) + " / " + Number(c.capacidad_max) + " kg";
        }

        filas += `<tr>
            <td>${c.id}</td>
            <td class="ajustar">${limpiarTexto(c.nombre)}</td>
            <td class="ajustar">${limpiarTexto(c.calle)}${c.zona ? ", " + limpiarTexto(c.zona) : ""}</td>
            <td>${capacidad}</td>
            <td class="ajustar">${limpiarTexto(residuos)}</td>
            <td>${limpiarTexto(c.operario)}</td>`;
        if (conAcciones) {
            filas += botonesAcciones("Centro", c.id);
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

function mostrarTablaMaquinaria(lista, conAcciones) {
    const tabla = document.getElementById("tablaMaquinaria");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(6, "No hay maquinaria");
        return;
    }

    let filas = "";
    for (const m of lista) {
        filas += `<tr>
            <td>${m.id}</td>
            <td>${limpiarTexto(m.nombre)}</td>
            <td>${limpiarTexto(m.tipo)}</td>
            <td><span class="etiqueta ${colorEstado(m.estado)}">${limpiarTexto(m.estado)}</span></td>
            <td>${limpiarTexto(m.instalacion)}</td>`;
        if (conAcciones) {
            filas += botonesAcciones("Maquina", m.id);
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

// Carga opciones en una lista desplegable (select).
// "lista" tiene objetos con id y nombre.
function llenarSelect(idSelect, lista, campoId) {
    const select = document.getElementById(idSelect);
    for (const item of lista) {
        select.innerHTML += `<option value="${item[campoId]}">${limpiarTexto(item.nombre)}</option>`;
    }
}

// Carga los tipos de residuo como casillas para marcar (en el formulario de centros)
function llenarCasillasResiduos(tipos) {
    const caja = document.getElementById("casillasResiduos");
    for (const tipo of tipos) {
        caja.innerHTML += `<label class="casilla">
            <input type="checkbox" name="residuo" value="${tipo.id_residuo}"> ${limpiarTexto(tipo.nombre)}
        </label>`;
    }
}

// Mantenimientos de un camion (dentro de la ventana de mantenimientos)
function mostrarTablaMantenimientos(lista, conAcciones) {
    const tabla = document.getElementById("tablaMantenimientos");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(5, "Este camión todavía no tiene mantenimientos");
        return;
    }

    let filas = "";
    for (const m of lista) {
        filas += `<tr>
            <td>${limpiarTexto(m.fecha)}</td>
            <td>${limpiarTexto(m.tipo)}</td>
            <td>${m.costo ? "$ " + Number(m.costo) : ""}</td>
            <td class="ajustar">${limpiarTexto(m.descripcion)}</td>`;
        if (conAcciones) {
            filas += `<td><button class="btn btn-danger btn-sm" data-click="borrarMantenimientoElegido" data-args="${m.id_camion},${m.nro}">Borrar</button></td>`;
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

function mostrarTablaRecorridos(lista, conAcciones) {
    const tabla = document.getElementById("tablaRecorridos");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(8, "No hay recorridos asignados");
        return;
    }

    let filas = "";
    for (const r of lista) {
        // Horario, por ejemplo "06:00 a 12:00"
        let horario = "";
        if (r.hora_inicio) horario = r.hora_inicio.substring(0, 5);
        if (r.hora_fin) horario += " a " + r.hora_fin.substring(0, 5);

        filas += `<tr>
            <td>${limpiarTexto(r.fecha_asignacion)}</td>
            <td>${limpiarTexto(r.ruta)}</td>
            <td>${limpiarTexto(r.cuadrilla)}</td>
            <td>${limpiarTexto(r.camion)}</td>
            <td>${horario}</td>
            <td class="ajustar">${limpiarTexto(r.zonas.join(", "))}</td>
            <td>${r.contenedores.length}</td>`;
        if (conAcciones) {
            filas += botonesAcciones("Recorrido", r.id);
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}

// Carga las zonas como casillas para marcar (en el formulario de recorridos).
// Si la zona ya la recorre otro recorrido, se avisa al lado.
function llenarCasillasZonas(zonas) {
    const caja = document.getElementById("casillasZonas");
    caja.innerHTML = "";
    for (const z of zonas) {
        let aviso = "";
        if (z.id_recorrido != null) {
            aviso = ` <small>(recorrido #${z.id_recorrido})</small>`;
        }
        caja.innerHTML += `<label class="casilla">
            <input type="checkbox" name="zona" value="${z.id}"> ${limpiarTexto(z.nombre)}${aviso}
        </label>`;
    }
}

// Nombre de la cuadrilla y la ruta de una incidencia (o "Sin asignar")
function textoCuadrilla(i) {
    if (i.cuadrilla == null) {
        return `<span class="texto-gris">Sin asignar</span>`;
    }
    return `${limpiarTexto(i.cuadrilla)}<br><small>${limpiarTexto(i.ruta)}</small>`;
}

// Tabla de incidencias. Cada incidencia trae "puedeCambiar" y "conHistorial" (los calcula el controlador)
// para saber si se muestran los botones de Seguimiento e Historial.
function mostrarTablaIncidencias(lista) {
    const tabla = document.getElementById("tablaIncidencias");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(8, "No hay incidencias");
        return;
    }

    let filas = "";
    for (const i of lista) {
        let botones = "";
        if (i.puedeCambiar) {
            botones += `<button class="btn btn-primary btn-sm" data-click="abrirSeguimiento" data-args="${i.id}">${traducir("Seguimiento")}</button>`;
        }
        if (i.conHistorial) {
            botones += `<button class="btn btn-secondary btn-sm" data-click="verHistorial" data-args="${i.id_contenedor}">${traducir("Historial")}</button>`;
        }

        // Donde esta exactamente y quien la reporto (si fue un vecino)
        let lugar = "";
        if (i.referencia) {
            lugar = `<br><small class="con-icono">${icono("pin")}${limpiarTexto(i.referencia)}</small>`;
        }
        let quien = "";
        if (i.vecino) {
            quien = `<br><small class="reportada-por con-icono">${icono("megafono")}Reportada por ${limpiarTexto(i.vecino)}</small>`;
        }

        filas += `<tr>
            <td>${i.id}</td>
            <td>${limpiarTexto(i.fecha_deteccion)}</td>
            <td class="ajustar">${limpiarTexto(i.calle)}<br><small>${limpiarTexto(i.zona_incidencia)} · Municipio ${limpiarTexto(i.municipio)}</small>${lugar}</td>
            <td class="ajustar descripcion-incidencia"><strong>${limpiarTexto(i.tipo)}</strong><br><small>${limpiarTexto(i.descripcion)}</small>${quien}</td>
            <td><span class="etiqueta ${colorPrioridad(i.prioridad)}">${limpiarTexto(i.prioridad)}</span></td>
            <td><span class="etiqueta ${colorEstado(i.estado)}">${limpiarTexto(i.estado)}</span></td>
            <td>${textoCuadrilla(i)}</td>
            <td class="acciones acciones-columna">${botones}</td>
        </tr>`;
    }
    tabla.innerHTML = filas;
}

// Historial de incidencias de un contenedor (en la ventana de Historial)
function mostrarTablaHistorial(lista) {
    const tabla = document.getElementById("tablaHistorial");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(5, "Este contenedor no tuvo incidencias");
        return;
    }

    let filas = "";
    for (const i of lista) {
        filas += `<tr>
            <td>${limpiarTexto(i.fecha_deteccion)}</td>
            <td>${limpiarTexto(i.tipo)}</td>
            <td><span class="etiqueta ${colorEstado(i.estado)}">${limpiarTexto(i.estado)}</span></td>
            <td>${limpiarTexto(i.fecha_resolucion ?? "-")}</td>
            <td class="ajustar">${limpiarTexto(i.descripcion)}</td>
        </tr>`;
    }
    tabla.innerHTML = filas;
}

// Tabla de recolecciones por contenedor (informe de volumen)
function mostrarTablaVolumen(lista) {
    const tabla = document.getElementById("tablaVolumen");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(6, "No hay contenedores");
        return;
    }

    let filas = "";
    for (const c of lista) {
        filas += `<tr>
            <td>${c.id}</td>
            <td class="ajustar">${limpiarTexto(c.calle)}<br><small>${limpiarTexto(c.zona)}</small></td>
            <td>${limpiarTexto(c.municipio)}</td>
            <td>${c.vaciados}</td>
            <td>${c.kilos} kg</td>
            <td>${limpiarTexto(c.ultima ?? "-")}</td>
        </tr>`;
    }
    tabla.innerHTML = filas;
}

// Tabla del informe de incidencias
function mostrarTablaInforme(lista) {
    const tabla = document.getElementById("tablaInforme");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(9, "No hay incidencias entre esas fechas");
        return;
    }

    let filas = "";
    for (const i of lista) {
        // Dias que tardo en resolverse (solo si ya esta resuelta)
        let dias = "-";
        if (i.fecha_resolucion != null) {
            dias = diasEntre(i.fecha_deteccion, i.fecha_resolucion);
        }
        filas += `<tr>
            <td>${i.id}</td>
            <td>${limpiarTexto(i.fecha_deteccion)}</td>
            <td class="ajustar">${limpiarTexto(i.calle)}</td>
            <td>${limpiarTexto(i.municipio)}</td>
            <td>${limpiarTexto(i.tipo)}</td>
            <td>${limpiarTexto(i.prioridad)}</td>
            <td>${limpiarTexto(i.estado)}</td>
            <td>${limpiarTexto(i.fecha_resolucion ?? "-")}</td>
            <td>${dias}</td>
        </tr>`;
    }
    tabla.innerHTML = filas;
}

// Tabla del inventario de repuestos. Los que estan en el minimo o menos se marcan en rojo.
// conAcciones: puede editar y borrar. Todo el personal puede usar (-1) o reponer (+1).
function mostrarTablaRepuestos(lista, conAcciones) {
    const tabla = document.getElementById("tablaRepuestos");

    if (lista.length == 0) {
        tabla.innerHTML = filaVacia(8, "No hay repuestos");
        return;
    }

    let filas = "";
    for (const r of lista) {
        let etiqueta = `<span class="etiqueta">${r.cantidad}</span>`;
        if (Number(r.cantidad) <= Number(r.cantidad_minima)) {
            etiqueta = `<span class="etiqueta mal" title="Hay que reponer">${icono("alerta")}${r.cantidad}</span>`;
        }
        filas += `<tr>
            <td>${r.id}</td>
            <td class="ajustar">${limpiarTexto(r.nombre)}</td>
            <td>${limpiarTexto(r.tipo)}</td>
            <td>${limpiarTexto(r.residuo ?? "-")}</td>
            <td class="ajustar">${limpiarTexto(r.instalacion ?? "Sin asignar")}</td>
            <td class="stock">
                <button class="btn btn-secondary btn-sm" data-click="usarRepuesto" data-args="${r.id},-1" title="Usar uno">−</button>
                ${etiqueta}
                <button class="btn btn-secondary btn-sm" data-click="usarRepuesto" data-args="${r.id},1" title="Agregar uno">+</button>
            </td>
            <td>${r.cantidad_minima}</td>`;
        if (conAcciones) {
            filas += botonesAcciones("Repuesto", r.id);
        }
        filas += `</tr>`;
    }
    tabla.innerHTML = filas;
}
