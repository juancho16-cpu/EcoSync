<?php
// Vista: la API siempre responde en formato JSON

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}
