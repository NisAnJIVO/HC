<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Huesped.php';

header('Content-Type: application/json');

if (!isset($_GET['ci']) || empty($_GET['ci'])) {
    echo json_encode(['error' => 'CI no proporcionado']);
    exit;
}

$ci = clean_input($_GET['ci']);

try {
    $huespedModel = new Huesped();
    $huesped = $huespedModel->buscarPorCI($ci);
    
    if ($huesped) {
        // Calcular edad si tiene fecha_nacimiento
        $edad = null;
        if (!empty($huesped['fecha_nacimiento'])) {
            $fecha_nac = new DateTime($huesped['fecha_nacimiento']);
            $hoy = new DateTime();
            $edad = $hoy->diff($fecha_nac)->y;
        }
        
        echo json_encode([
            'encontrado' => true,
            'datos' => [
                'nombres_apellidos' => $huesped['nombres_apellidos'],
                'ci_pasaporte' => $huesped['ci_pasaporte'],
                'genero' => $huesped['genero'],
                'edad' => $edad ?? $huesped['edad'],
                'fecha_nacimiento' => $huesped['fecha_nacimiento'] ?? null,
                'estado_civil' => $huesped['estado_civil'],
                'nacionalidad' => $huesped['nacionalidad'],
                'profesion' => $huesped['profesion'],
                'objeto' => $huesped['objeto'],
                'procedencia' => $huesped['procedencia']
            ]
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al buscar huésped: ' . $e->getMessage()]);
}
