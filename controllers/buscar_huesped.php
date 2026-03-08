<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Huesped.php';

header('Content-Type: application/json');

if (isset($_GET['ci'])) {
    $ci = clean_input($_GET['ci']);
    $huespedModel = new Huesped();
    $huesped = $huespedModel->buscarPorCI($ci);
    
    if ($huesped) {
        echo json_encode([
            'success' => true,
            'huesped' => $huesped
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Huésped no encontrado'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'CI no proporcionado'
    ]);
}
?>
