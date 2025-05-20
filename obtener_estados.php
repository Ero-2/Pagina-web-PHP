<?php
header('Content-Type: application/json');

// Leer el parámetro 'pais' desde la URL (GET)
$paisNombre = $_GET['pais'] ?? null;

if (!$paisNombre) {
    echo json_encode(['error' => 'Falta el nombre del país']);
    exit;
}

// Preparar solicitud a la API externa
$url = "https://countriesnow.space/api/v0.1/countries/states ";
$postData = json_encode(['country' => $paisNombre]);

$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ],
    CURLOPT_FAILONERROR    => false, // Importante para recibir cuerpo aunque haya error HTTP
]);

$response   = curl_exec($curl);
$httpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlErr    = curl_error($curl);
curl_close($curl);

if ($response === false) {
    echo json_encode([
        'error' => 'cURL error: ' . $curlErr,
        'httpCode' => $httpCode
    ]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['data']['states']) || !is_array($data['data']['states'])) {
    echo json_encode([
        'error' => 'Formato inesperado en respuesta del servidor',
        'httpCode' => $httpCode,
        'response' => $data
    ]);
    exit;
}

$estados = array_map(function ($s) {
    return [
        'name' => $s['name'],
        'id'   => $s['state_code'] ?? $s['name']
    ];
}, $data['data']['states']);

echo json_encode($estados);
