<?php
// auth.php

function verificar_token() {
    // Token fijo (para ejemplo simple, se puede mejorar luego con JWT)
    $token_valido = "12345ABCD";

    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token no proporcionado"]);
        exit;
    }

    $token = trim(str_replace("Bearer", "", $headers['Authorization']));

    if ($token !== $token_valido) {
        http_response_code(403);
        echo json_encode(["error" => "Token inválido"]);
        exit;
    }
}
