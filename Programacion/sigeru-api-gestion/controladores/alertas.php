<?php
// Controlador de las alertas (GET alertas). Cada rol ve las alertas que le sirven.
// Cada alerta tiene: nivel (alta, media o info), texto y enlace (la pagina donde se resuelve).

function listarAlertas($alerta, $parametro) {
    $usuario = $_SESSION['usuario_logueado'];
    $alertas = [];

    // Los limites salen de los parametros del sistema (los puede cambiar el administrador)
    $dias = intval($parametro->obtener("dias_alerta_desborde", "2"));
    $porcentaje = floatval($parametro->obtener("porcentaje_centro_lleno", "80"));

    if ($usuario['rol'] == "Administrador" || $usuario['rol'] == "Operario") {
        foreach ($alerta->desbordadosSinAtender($dias) as $a) {
            $alertas[] = ["nivel" => "alta", "texto" => "El contenedor de " . $a['calle'] . " (" . $a['zona'] . ") está desbordado hace " . $a['dias'] . " días y nadie lo atendió", "enlace" => "incidencias.html"];
        }
        foreach ($alerta->prioridadAltaAbiertas($dias) as $a) {
            $alertas[] = ["nivel" => "alta", "texto" => "La incidencia #" . $a['id'] . " en " . $a['calle'] . " es de prioridad alta y sigue abierta", "enlace" => "incidencias.html"];
        }
        foreach ($alerta->avisosSinAtender() as $a) {
            $alertas[] = ["nivel" => "media", "texto" => "La incidencia #" . $a['id'] . " que reportó un vecino en " . $a['calle'] . " sigue sin atender", "enlace" => "incidencias.html"];
        }
        foreach ($alerta->instalacionesCasiLlenas($porcentaje) as $a) {
            $alertas[] = ["nivel" => "media", "texto" => $a['nombre'] . " está al " . $a['porcentaje'] . "% de su capacidad", "enlace" => "centros.html"];
        }
        foreach ($alerta->repuestosConPocoStock() as $a) {
            $alertas[] = ["nivel" => "media", "texto" => "Quedan pocos repuestos de " . $a['nombre'] . ": " . $a['cantidad'] . " (mínimo " . $a['cantidad_minima'] . ")", "enlace" => "repuestos.html"];
        }
    }

    if ($usuario['rol'] == "Recolector") {
        foreach ($alerta->delRecolector($usuario['id']) as $a) {
            $nivel = "media";
            if ($a['prioridad'] == "Alta") {
                $nivel = "alta";
            }
            $alertas[] = ["nivel" => $nivel, "texto" => "Tu cuadrilla tiene que atender la incidencia #" . $a['id'] . " en " . $a['calle'] . " (" . $a['estado'] . ")", "enlace" => "incidencias.html"];
        }
    }

    if ($usuario['rol'] == "Vecino") {
        foreach ($alerta->avisosDelVecino($usuario['id']) as $a) {
            if ($a['estado'] == "Resuelta") {
                $texto = "¡Tu incidencia #" . $a['id'] . " en el contenedor de " . $a['calle'] . " ya fue resuelta!";
            } else {
                $texto = "Tu incidencia #" . $a['id'] . " en el contenedor de " . $a['calle'] . " está en proceso";
            }
            $alertas[] = ["nivel" => "info", "texto" => $texto, "enlace" => "incidencias.html"];
        }
    }

    responder($alertas);
}
