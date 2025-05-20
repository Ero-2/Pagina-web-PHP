<?php
header('Content-Type: application/json');

// URL de la API para obtener países con sus estados
// Este es el mismo endpoint que se utiliza para obtener estados,
// garantizando consistencia en los nombres de países
$url = "https://countriesnow.space/api/v0.1/countries/states";

$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR => true,
    CURLOPT_SSL_VERIFYPEER => false // Puede ser necesario en algunos servidores
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);

if (!$response) {
    echo json_encode(['error' => 'Error en la solicitud: ' . $curlError]);
    exit;
}
curl_close($curl);

$data = json_decode($response, true);

$paises = [];
if ($data && isset($data['data'])) {
    foreach ($data['data'] as $pais) {
        $paises[] = [
            'name' => $pais['name'],
            'iso' => $pais['iso3'] ?? '',
            'has_states' => count($pais['states'] ?? []) > 0
        ];
    }
    
    // Ordenar países alfabéticamente
    usort($paises, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
} else {
    echo json_encode(['error' => 'No se pudieron cargar los países']);
    exit;
}

echo json_encode($paises);
?>