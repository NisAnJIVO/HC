<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Mantenimiento.php';

// Crear instancia del modelo
$mantenimientoModel = new Mantenimiento();
$mantenimientos = $mantenimientoModel->obtenerActivos();

// Crear nuevo PDF con TCPDF
class MYPDF extends TCPDF {
    // Encabezado personalizado
    public function Header() {
        // Logo (solo si GD está disponible para evitar errores)
        if (extension_loaded('gd') || extension_loaded('imagick')) {
            $logo_path = __DIR__ . '/../../assets/img/logo.png';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
        }
        
        // Fuente del encabezado
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(26, 32, 44); // Color oscuro elegante
        
        // Título
        $this->Cell(0, 15, 'HOTEL CECIL', 0, true, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->SetFont('helvetica', '', 12);
        $this->SetTextColor(74, 85, 104);
        $this->Cell(0, 5, 'Informe de Mantenimientos de Habitaciones', 0, true, 'C', 0, '', 0, false, 'M', 'M');
        
        // Línea decorativa
        $this->SetLineStyle(array('width' => 0.5, 'color' => array(226, 232, 240)));
        $this->Line(15, 38, 195, 38);
        
        $this->Ln(5);
    }
    
    // Pie de página personalizado
    public function Footer() {
        // Posición a 15 mm del final
        $this->SetY(-15);
        
        // Línea decorativa
        $this->SetLineStyle(array('width' => 0.5, 'color' => array(226, 232, 240)));
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        
        $this->Ln(2);
        
        // Fuente del pie
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(160, 174, 192);
        
        // Fecha de generación y página
        $fecha_generacion = date('d/m/Y H:i');
        $this->Cell(0, 5, 'Generado el ' . $fecha_generacion . ' | Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Crear documento PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Información del documento
$pdf->SetCreator('Hotel Cecil');
$pdf->SetAuthor('Hotel Cecil');
$pdf->SetTitle('Informe de Mantenimientos');
$pdf->SetSubject('Reporte de Mantenimientos de Habitaciones');

// Configuración de márgenes
$pdf->SetMargins(15, 45, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 20);

// Fuente predeterminada
$pdf->SetFont('helvetica', '', 10);

// Agregar página
$pdf->AddPage();

// Color de fondo para encabezados de sección
$header_bg = array(247, 250, 252);
$border_color = array(226, 232, 240);

// Verificar si hay mantenimientos
if (empty($mantenimientos)) {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(113, 128, 150);
    $pdf->Cell(0, 50, 'No hay mantenimientos activos registrados', 0, true, 'C', 0, '', 0, false, 'M', 'M');
} else {
    // Información general
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(45, 55, 72);
    $pdf->SetFillColor(237, 242, 247);
    $pdf->Cell(0, 8, 'Resumen General', 0, true, 'L', true, '', 0, false, 'M', 'M');
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(74, 85, 104);
    $pdf->Cell(0, 6, 'Total de mantenimientos activos: ' . count($mantenimientos), 0, true, 'L', 0, '', 0, false, 'M', 'M');
    $pdf->Cell(0, 6, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, true, 'L', 0, '', 0, false, 'M', 'M');
    
    $pdf->Ln(5);
    
    // Contador de mantenimientos
    $contador = 1;
    
    // Recorrer cada mantenimiento
    foreach ($mantenimientos as $mant) {
        // Verificar si necesita nueva página
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        
        // Configurar colores según prioridad
        $prioridad_colors = [
            'baja' => ['r' => 237, 'g' => 242, 'b' => 247, 'text_r' => 74, 'text_g' => 85, 'text_b' => 104],
            'media' => ['r' => 219, 'g' => 234, 'b' => 254, 'text_r' => 30, 'text_g' => 64, 'text_b' => 175],
            'alta' => ['r' => 254, 'g' => 243, 'b' => 199, 'text_r' => 180, 'text_g' => 83, 'text_b' => 9],
            'urgente' => ['r' => 254, 'g' => 226, 'b' => 226, 'text_r' => 153, 'text_g' => 27, 'text_b' => 27]
        ];
        
        $color = $prioridad_colors[$mant['prioridad']];
        
        // Marco del mantenimiento
        $pdf->SetLineStyle(array('width' => 0.3, 'color' => $border_color));
        
        // Encabezado del mantenimiento
        $pdf->SetFillColor($color['r'], $color['g'], $color['b']);
        $pdf->SetTextColor($color['text_r'], $color['text_g'], $color['text_b']);
        $pdf->SetFont('helvetica', 'B', 12);
        
        $y_start = $pdf->GetY();
        $pdf->MultiCell(0, 8, 'Mantenimiento #' . $contador . ' - Habitación ' . $mant['habitacion_numero'], 0, 'L', true, 1, '', '', true, 0, false, true, 8, 'M');
        
        // Contenedor con borde
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Rect($pdf->GetX(), $y_start, 180, 0, 'D');
        
        $pdf->Ln(1);
        
        // Guardar posición Y inicial para este mantenimiento
        $y_start_content = $pdf->GetY();
        
        // COLUMNA IZQUIERDA - Información del mantenimiento (90mm de ancho)
        $col_left_width = 90;
        $col_right_width = 85;
        $x_left = 15;
        $x_right = 110;
        
        // Variable para rastrear la altura máxima (contenido vs imagen)
        $y_max_left = $y_start_content;
        $y_max_right = $y_start_content;
        
        // Título
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(26, 32, 44);
        $pdf->SetXY($x_left, $y_start_content);
        $pdf->MultiCell($col_left_width, 4, htmlspecialchars($mant['titulo']), 0, 'L', 0, 1, '', '', true, 0, false, true, 4, 'T');
        
        // Badges de tipo, prioridad y estado en una sola línea más compacta
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(113, 128, 150);
        
        $tipo_texto = ucfirst($mant['tipo']);
        $prioridad_texto = ucfirst($mant['prioridad']);
        $estado_texto = ucfirst(str_replace('_', ' ', $mant['estado']));
        
        $pdf->SetX($x_left);
        $pdf->Cell(28, 3.5, 'Tipo: ' . $tipo_texto, 0, 0, 'L', 0, '', 0, false, 'T', 'T');
        $pdf->Cell(32, 3.5, 'Prior.: ' . $prioridad_texto, 0, 0, 'L', 0, '', 0, false, 'T', 'T');
        $pdf->Cell(0, 3.5, 'Estado: ' . $estado_texto, 0, 1, 'L', 0, '', 0, false, 'T', 'T');
        
        $pdf->Ln(1);
        
        // Descripción compacta MEJORADA LEGIBILIDAD
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(45, 55, 72); // Color más oscuro para mejor contraste
        $pdf->SetX($x_left);
        $pdf->Cell($col_left_width, 3.5, 'Descripción:', 0, 1, 'L', 0, '', 0, false, 'T', 'T');
        
        $pdf->SetFont('helvetica', '', 9); // Aumentado de 8 a 9
        $pdf->SetTextColor(26, 32, 44); // Color más oscuro para mejor legibilidad
        $pdf->SetX($x_left);
        // Removido límite de altura para que muestre toda la descripción
        $pdf->MultiCell($col_left_width, 4, htmlspecialchars($mant['descripcion']), 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T');
        
        $pdf->Ln(0.5);
        
        // Información detallada compacta
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(74, 85, 104);
        
        // Fecha Inicio
        $pdf->SetX($x_left);
        $pdf->Cell(35, 3.5, 'Fecha Inicio:', 0, 0, 'L', 0, '', 0, false, 'T', 'T');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 3.5, date('d/m/Y', strtotime($mant['fecha_inicio'])), 0, 1, 'L', 0, '', 0, false, 'T', 'T');
        
        // Fecha Fin Estimada
        if ($mant['fecha_fin_estimada']) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetX($x_left);
            $pdf->Cell(35, 3.5, 'Fecha Fin Est.:', 0, 0, 'L', 0, '', 0, false, 'T', 'T');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 3.5, date('d/m/Y', strtotime($mant['fecha_fin_estimada'])), 0, 1, 'L', 0, '', 0, false, 'T', 'T');
        }
        
        // Responsable
        if ($mant['responsable']) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetX($x_left);
            $pdf->Cell(35, 3.5, 'Responsable:', 0, 0, 'L', 0, '', 0, false, 'T', 'T');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 3.5, htmlspecialchars($mant['responsable']), 0, 1, 'L', 0, '', 0, false, 'T', 'T');
        }
        
        // Costo Estimado
        if ($mant['costo_estimado']) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetX($x_left);
            $pdf->Cell(35, 3.5, 'Costo Estimado:', 0, 0, 'L', 0, '', 0, false, 'T', 'T');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 3.5, 'Bs. ' . number_format($mant['costo_estimado'], 2), 0, 1, 'L', 0, '', 0, false, 'T', 'T');
        }
        
        // Observaciones
        if ($mant['observaciones']) {
            $pdf->Ln(0.5);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetX($x_left);
            $pdf->Cell($col_left_width, 3.5, 'Observaciones:', 0, 1, 'L', 0, '', 0, false, 'T', 'T');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetX($x_left);
            $pdf->MultiCell($col_left_width, 3.5, htmlspecialchars($mant['observaciones']), 0, 'L', 0, 1, '', '', true, 0, false, true, 3.5, 'T');
        }
        
        // Guardar posición Y final de la columna izquierda
        $y_max_left = $pdf->GetY();
        
        // COLUMNA DERECHA - Imagen (si existe)
        if ($mant['imagen']) {
            $imagen_path = __DIR__ . '/../../assets/img/Mantenimiento/' . $mant['imagen'];
            if (file_exists($imagen_path)) {
                try {
                    // Obtener información de la imagen
                    $image_info = getimagesize($imagen_path);
                    if ($image_info === false) {
                        throw new Exception('No se pudo leer la imagen');
                    }
                    
                    list($img_width, $img_height, $img_type) = $image_info;
                    
                    // Determinar el tipo de imagen
                    $imagen_procesada = $imagen_path;
                    
                    // Si es PNG, convertir a JPG para evitar problemas con canal alpha
                    if ($img_type === IMAGETYPE_PNG) {
                        $source = imagecreatefrompng($imagen_path);
                        $bg = imagecreatetruecolor($img_width, $img_height);
                        $white = imagecolorallocate($bg, 255, 255, 255);
                        imagefill($bg, 0, 0, $white);
                        imagecopy($bg, $source, 0, 0, 0, 0, $img_width, $img_height);
                        $imagen_procesada = __DIR__ . '/../../assets/img/Mantenimiento/temp_' . time() . '.jpg';
                        imagejpeg($bg, $imagen_procesada, 90);
                        imagedestroy($source);
                        imagedestroy($bg);
                    }
                    // Si es GIF, convertir a JPG
                    else if ($img_type === IMAGETYPE_GIF) {
                        $source = imagecreatefromgif($imagen_path);
                        $bg = imagecreatetruecolor($img_width, $img_height);
                        $white = imagecolorallocate($bg, 255, 255, 255);
                        imagefill($bg, 0, 0, $white);
                        imagecopy($bg, $source, 0, 0, 0, 0, $img_width, $img_height);
                        $imagen_procesada = __DIR__ . '/../../assets/img/Mantenimiento/temp_' . time() . '.jpg';
                        imagejpeg($bg, $imagen_procesada, 90);
                        imagedestroy($source);
                        imagedestroy($bg);
                    }
                    // Para WEBP
                    else if (function_exists('imagecreatefromwebp') && $img_type === IMAGETYPE_WEBP) {
                        $source = imagecreatefromwebp($imagen_path);
                        $bg = imagecreatetruecolor($img_width, $img_height);
                        $white = imagecolorallocate($bg, 255, 255, 255);
                        imagefill($bg, 0, 0, $white);
                        imagecopy($bg, $source, 0, 0, 0, 0, $img_width, $img_height);
                        $imagen_procesada = __DIR__ . '/../../assets/img/Mantenimiento/temp_' . time() . '.jpg';
                        imagejpeg($bg, $imagen_procesada, 90);
                        imagedestroy($source);
                        imagedestroy($bg);
                    }
                    
                    // Calcular tamaño de imagen para que quepa en columna derecha
                    $max_width = $col_right_width;
                    $max_height = 50; // Altura máxima reducida en 60% (antes era 120mm)
                    
                    $ratio = min($max_width / $img_width, $max_height / $img_height);
                    $new_width = $img_width * $ratio;
                    $new_height = $img_height * $ratio;
                    
                    // Posicionar imagen en columna derecha, centrada verticalmente con el contenido
                    $y_image = $y_start_content + 2;
                    
                    // Agregar imagen con borde
                    $pdf->SetLineStyle(array('width' => 0.3, 'color' => array(203, 213, 224)));
                    $pdf->Image($imagen_procesada, $x_right, $y_image, $new_width, $new_height, '', '', '', false, 300, '', false, false, 1, false, false, false);
                    
                    // Calcular la posición Y final de la imagen
                    $y_max_right = $y_image + $new_height + 2;
                    
                    // Eliminar archivo temporal si se creó
                    if ($imagen_procesada !== $imagen_path && file_exists($imagen_procesada)) {
                        @unlink($imagen_procesada);
                    }
                    
                } catch (Exception $e) {
                    // Si hay error con la imagen, mostrar mensaje en columna derecha
                    $pdf->SetFont('helvetica', 'I', 7);
                    $pdf->SetTextColor(160, 174, 192);
                    $pdf->SetXY($x_right, $y_start_content + 2);
                    $pdf->MultiCell($col_right_width, 3, '[Imagen no disponible]', 0, 'C', 0, 1, '', '', true, 0, false, true, 3, 'M');
                }
            }
        }
        
        // Mover el cursor a la posición Y máxima (la más baja entre contenido e imagen)
        $y_final = max($y_max_left, $y_max_right);
        $pdf->SetY($y_final);

        
        // Línea separadora entre mantenimientos
        $pdf->Ln(3);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        $contador++;
    }
}

// Salida del PDF
$pdf->Output('Informe_Mantenimientos_' . date('Y-m-d') . '.pdf', 'I');
?>
