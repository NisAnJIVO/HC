<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/CierreCaja.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    die('Acceso denegado');
}

// Verificar que se haya proporcionado un ID de cierre
if (!isset($_GET['id'])) {
    die('ID de cierre no proporcionado');
}

$cierre_id = intval($_GET['id']);
$cierreModel = new CierreCaja();

// Obtener datos del cierre
$cierre = $cierreModel->obtenerCierrePorId($cierre_id);

if (!$cierre) {
    die('Cierre no encontrado');
}

// Obtener detalles
$ingresos = $cierreModel->obtenerDetallesIngresosCierre($cierre_id);
$egresos = $cierreModel->obtenerDetallesEgresosCierre($cierre_id);
$pagos_qr = $cierreModel->obtenerDetallesPagosQRCierre($cierre_id);

// Crear PDF
$pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

// Configuración del documento
$pdf->SetCreator('Hotel Cecil');
$pdf->SetAuthor('Hotel Cecil');
$pdf->SetTitle('Cierre de Caja #' . $cierre['id']);
$pdf->SetSubject('Rendición de Cuentas');

// Desactivar header y footer por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configuración de página
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// Configuración de fuente
$pdf->SetFont('helvetica', '', 10);

// ===== ENCABEZADO =====
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 10, 'HOTEL CECIL', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'RENDICIÓN DE CUENTAS - CIERRE DE CAJA', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Cierre #' . $cierre['id'], 0, 1, 'C');

$pdf->Ln(5);

// ===== INFORMACIÓN DEL CIERRE =====
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'INFORMACIÓN DEL CIERRE', 0, 1, 'L', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->Ln(2);

// Fecha de apertura
$pdf->Cell(45, 5, 'Apertura de Caja:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, date('d/m/Y H:i:s', strtotime($cierre['fecha_apertura'])), 0, 1, 'L');

// Fecha de cierre
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(45, 5, 'Cierre de Caja:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, date('d/m/Y H:i:s', strtotime($cierre['fecha_cierre'])), 0, 1, 'L');

// Usuario
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(45, 5, 'Cerrado por:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, $cierre['usuario_nombre'], 0, 1, 'L');

$pdf->Ln(5);

// ===== RESUMEN FINANCIERO =====
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'RESUMEN FINANCIERO', 0, 1, 'L', true);

$pdf->Ln(3);

// Tabla de resumen
$pdf->SetFillColor(230, 255, 230); // Verde claro para efectivo
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(130, 7, 'Ingresos en Efectivo', 1, 0, 'L', true);
$pdf->Cell(50, 7, 'Bs. ' . number_format($cierre['total_efectivo'], 2), 1, 1, 'R', true);

$pdf->SetFillColor(230, 230, 255); // Azul claro para QR
$pdf->Cell(130, 7, 'Ingresos por QR', 1, 0, 'L', true);
$pdf->Cell(50, 7, 'Bs. ' . number_format($cierre['total_qr'], 2), 1, 1, 'R', true);

$pdf->SetFillColor(255, 230, 230); // Rojo claro para egresos
$pdf->Cell(130, 7, 'Egresos (Gastos)', 1, 0, 'L', true);
$pdf->Cell(50, 7, 'Bs. ' . number_format($cierre['total_egresos'], 2), 1, 1, 'R', true);

$pdf->Ln(2);

$pdf->SetFillColor(255, 255, 200); // Amarillo claro
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(130, 8, 'Balance de Efectivo (Efectivo - Egresos)', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Bs. ' . number_format($cierre['balance_efectivo'], 2), 1, 1, 'R', true);

$pdf->SetFillColor(200, 230, 255); // Azul claro
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(130, 9, 'BALANCE TOTAL', 1, 0, 'L', true);
$pdf->Cell(50, 9, 'Bs. ' . number_format($cierre['balance_total'], 2), 1, 1, 'R', true);

$pdf->Ln(5);

// ===== OBSERVACIONES =====
if (!empty($cierre['observaciones'])) {
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'OBSERVACIONES', 0, 1, 'L', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $cierre['observaciones'], 1, 'L');
    $pdf->Ln(5);
}

// ===== DETALLE DE INGRESOS =====
$pdf->AddPage();
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'DETALLE DE INGRESOS', 0, 1, 'L', true);

$pdf->Ln(2);

if (!empty($ingresos)) {
    // Encabezados de tabla
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(20, 6, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(80, 6, 'Concepto', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Método', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Monto', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('helvetica', '', 8);
    foreach ($ingresos as $ing) {
        $pdf->Cell(20, 5, date('d/m/Y', strtotime($ing['fecha'])), 1, 0, 'C');
        $pdf->Cell(80, 5, substr($ing['concepto'], 0, 50), 1, 0, 'L');
        $pdf->Cell(25, 5, strtoupper($ing['metodo_pago']), 1, 0, 'C');
        $pdf->Cell(30, 5, 'Bs. ' . number_format($ing['monto'], 2), 1, 1, 'R');
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(125, 6, 'TOTAL INGRESOS', 1, 0, 'R');
    $pdf->Cell(30, 6, 'Bs. ' . number_format($cierre['total_efectivo'] + $cierre['total_qr'], 2), 1, 1, 'R');
} else {
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 6, 'No hay ingresos registrados en este período', 0, 1, 'C');
}

$pdf->Ln(8);

// ===== DETALLE DE EGRESOS =====
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'DETALLE DE EGRESOS', 0, 1, 'L', true);

$pdf->Ln(2);

if (!empty($egresos)) {
    // Encabezados de tabla
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(20, 6, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(70, 6, 'Concepto', 1, 0, 'C', true);
    $pdf->Cell(35, 6, 'Categoría', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Monto', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('helvetica', '', 8);
    foreach ($egresos as $egr) {
        $pdf->Cell(20, 5, date('d/m/Y', strtotime($egr['fecha'])), 1, 0, 'C');
        $pdf->Cell(70, 5, substr($egr['concepto'], 0, 40), 1, 0, 'L');
        $pdf->Cell(35, 5, $egr['categoria'] ?? '-', 1, 0, 'C');
        $pdf->Cell(30, 5, 'Bs. ' . number_format($egr['monto'], 2), 1, 1, 'R');
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(125, 6, 'TOTAL EGRESOS', 1, 0, 'R');
    $pdf->Cell(30, 6, 'Bs. ' . number_format($cierre['total_egresos'], 2), 1, 1, 'R');
} else {
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 6, 'No hay egresos registrados en este período', 0, 1, 'C');
}

$pdf->Ln(8);

// ===== DETALLE DE PAGOS QR =====
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'DETALLE DE PAGOS QR', 0, 1, 'L', true);

$pdf->Ln(2);

if (!empty($pagos_qr)) {
    // Encabezados de tabla
    $pdf->SetFillColor(220, 220, 255); // Morado claro
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(20, 6, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Tipo', 1, 0, 'C', true);
    $pdf->Cell(70, 6, 'Concepto/Huésped', 1, 0, 'C', true);
    $pdf->Cell(35, 6, 'Nro Transacción', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Monto', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('helvetica', '', 7);
    $total_qr_detalle = 0;
    foreach ($pagos_qr as $pqr) {
        $total_qr_detalle += $pqr['monto'];
        $es_externo = ($pqr['tipo'] ?? 'huesped') === 'externo';
        
        $pdf->Cell(20, 5, date('d/m/Y', strtotime($pqr['fecha'])), 1, 0, 'C');
        $pdf->Cell(20, 5, $es_externo ? 'Externo' : 'Huésped', 1, 0, 'C');
        
        // Concepto/Huésped
        $concepto_texto = '';
        if ($es_externo && !empty($pqr['concepto'])) {
            $concepto_texto = substr($pqr['concepto'], 0, 40);
        } elseif (!empty($pqr['nombres_apellidos'])) {
            $concepto_texto = substr($pqr['nombres_apellidos'], 0, 30) . ' (Hab ' . $pqr['nro_pieza'] . ')';
        } else {
            $concepto_texto = 'Sin detalles';
        }
        
        $pdf->Cell(70, 5, $concepto_texto, 1, 0, 'L');
        $pdf->Cell(35, 5, substr($pqr['numero_transaccion'] ?? '-', 0, 15), 1, 0, 'C');
        $pdf->Cell(30, 5, 'Bs. ' . number_format($pqr['monto'], 2), 1, 1, 'R');
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(145, 6, 'TOTAL PAGOS QR', 1, 0, 'R');
    $pdf->Cell(30, 6, 'Bs. ' . number_format($total_qr_detalle, 2), 1, 1, 'R');
} else {
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 6, 'No hay pagos QR registrados en este período', 0, 1, 'C');
}

// ===== FIRMAS =====
$pdf->Ln(15);

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(90, 5, '', 0, 0);
$pdf->Cell(90, 5, '', 0, 1);

$pdf->Ln(10);

$pdf->Cell(90, 5, '________________________', 0, 0, 'C');
$pdf->Cell(90, 5, '________________________', 0, 1, 'C');

$pdf->Cell(90, 5, 'Firma Recepcionista', 0, 0, 'C');
$pdf->Cell(90, 5, 'Firma Dueño/Gerente', 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 8);
$pdf->Ln(3);
$pdf->Cell(90, 5, $cierre['usuario_nombre'], 0, 0, 'C');
$pdf->Cell(90, 5, '', 0, 1, 'C');

// ===== PIE DE PÁGINA =====
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 5, 'Documento generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Cell(0, 5, 'Hotel Cecil - Sistema de Gestión', 0, 1, 'C');

// Salida del PDF
$pdf->Output('Cierre_Caja_' . $cierre['id'] . '.pdf', 'I');
?>
