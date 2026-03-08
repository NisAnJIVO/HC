<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';
require_once __DIR__ . '/../../models/Huesped.php';

$page_title = 'Parte Diario - Planilla de Pasajeros';

// Obtener mes y año (por defecto el actual)
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Validar mes y año
if ($mes < 1 || $mes > 12) $mes = date('n');
if ($anio < 2020 || $anio > 2050) $anio = date('Y');

$registroModel = new RegistroOcupacion();
$huespedModel = new Huesped();

// Obtener todas las ocupaciones del mes
$fecha_inicio = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('t', strtotime($fecha_inicio));
$fecha_fin = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-$ultimo_dia";

// Query para obtener todas las ocupaciones que afectan el mes
$conn = getConnection();
$sql = "SELECT ro.*, h.* 
        FROM registro_ocupacion ro
        INNER JOIN huespedes h ON ro.huesped_id = h.id
        WHERE (ro.fecha_ingreso BETWEEN :inicio1 AND :fin1
               OR ro.fecha_salida_estimada BETWEEN :inicio2 AND :fin2
               OR (ro.fecha_ingreso <= :inicio3 AND ro.fecha_salida_estimada >= :fin3))
        ORDER BY ro.id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':inicio1' => $fecha_inicio, 
    ':fin1' => $fecha_fin,
    ':inicio2' => $fecha_inicio, 
    ':fin2' => $fecha_fin,
    ':inicio3' => $fecha_inicio, 
    ':fin3' => $fecha_fin
]);
$ocupaciones = $stmt->fetchAll();

// Organizar por día: ingresantes, pernoctantes y salientes
$ingresantes_por_dia = [];
$pernoctantes_por_dia = [];
$salientes_por_dia = [];

foreach ($ocupaciones as $ocu) {
    $fecha_ing = strtotime($ocu['fecha_ingreso']);
    $fecha_sal = strtotime($ocu['fecha_salida_estimada']);
    
    for ($dia = 1; $dia <= $ultimo_dia; $dia++) {
        $fecha_actual = strtotime("$anio-$mes-$dia");
        
        // Ingresante: si ingresa este día
        if (date('Y-m-d', $fecha_ing) == date('Y-m-d', $fecha_actual)) {
            if (!isset($ingresantes_por_dia[$dia])) {
                $ingresantes_por_dia[$dia] = [];
            }
            $ingresantes_por_dia[$dia][] = $ocu;
        }
        // Saliente: si sale este día
        elseif (date('Y-m-d', $fecha_sal) == date('Y-m-d', $fecha_actual)) {
            if (!isset($salientes_por_dia[$dia])) {
                $salientes_por_dia[$dia] = [];
            }
            $salientes_por_dia[$dia][] = $ocu;
        }
        // Pernoctante: si está entre ingreso y salida (no ingresa ni sale este día)
        elseif ($fecha_actual > $fecha_ing && $fecha_actual < $fecha_sal) {
            if (!isset($pernoctantes_por_dia[$dia])) {
                $pernoctantes_por_dia[$dia] = [];
            }
            $pernoctantes_por_dia[$dia][] = $ocu;
        }
    }
}

// Nombre del mes en español
$meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 
          'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
$nombre_mes = $meses[$mes];

include __DIR__ . '/../../includes/header.php';
?>

<style>
@media print {
    /* Ocultar todo el body por defecto */
    body * {
        visibility: hidden;
    }
    
    /* Mostrar solo la tabla del parte diario */
    .parte-diario,
    .parte-diario * {
        visibility: visible;
    }
    
    .parte-diario {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white !important;
        padding: 10px;
    }
    
    /* Ocultar elementos no deseados */
    .no-print,
    header,
    nav,
    footer,
    .sidebar {
        display: none !important;
    }
    
    table {
        page-break-inside: auto;
    }
    
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    @page {
        size: portrait;
        margin: 0.5cm 0.5cm 0.5cm 0.5cm;
    }
    
    /* Eliminar encabezados y pies de página del navegador */
    html {
        margin: 0 !important;
    }
    
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    /* Forzar impresión de colores */
    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}

.parte-diario {
    background: white;
    padding: 8px;
    font-family: Arial, sans-serif;
    font-size: 7.5px;
}

.parte-diario table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    table-layout: fixed;
}

.parte-diario th,
.parte-diario td {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.parte-diario th {
    background-color: #e0e0e0;
    font-weight: bold;
    font-size: 7px;
}

.parte-diario .header-row {
    background-color: #4472C4;
    color: #000000;
    font-weight: bold;
    text-align: center;
}

.parte-diario .fecha-row {
    background-color: #87CEEB !important;
    font-weight: bold;
    text-align: center;
    color: #000 !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

.parte-diario .sin-novedad {
    background-color: #FFFFFF !important;
    color: #666;
    font-weight: normal;
    text-align: center;
    font-style: italic;
}

.parte-diario .ingresantes-header {
    background-color: #90EE90 !important;
    font-weight: bold;
    color: #000 !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

.parte-diario .pernoctantes-header {
    background-color: #FFA500 !important;
    font-weight: bold;
    color: #000 !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

.parte-diario .salientes-header {
    background-color: #FF6B6B !important;
    font-weight: bold;
    color: #000 !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

.parte-diario .lista-personas {
    background-color: #FFFFFF !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

/* Anchos fijos para cada columna de datos */
.parte-diario .lista-personas td:nth-child(1) { width: 4%; min-width: 4%; max-width: 4%; }   /* N° */
.parte-diario .lista-personas td:nth-child(2) { width: 20%; min-width: 20%; max-width: 20%; }  /* Nombres */
.parte-diario .lista-personas td:nth-child(3) { width: 3%; min-width: 3%; max-width: 3%; }   /* Género */
.parte-diario .lista-personas td:nth-child(4) { width: 3%; min-width: 3%; max-width: 3%; }   /* Edad */
.parte-diario .lista-personas td:nth-child(5) { width: 3%; min-width: 3%; max-width: 3%; }   /* E.C. */
.parte-diario .lista-personas td:nth-child(6) { width: 8%; min-width: 8%; max-width: 8%; }   /* Nacionalidad */
.parte-diario .lista-personas td:nth-child(7) { width: 10%; min-width: 10%; max-width: 10%; }  /* CI/Pasaporte */
.parte-diario .lista-personas td:nth-child(8) { width: 14%; min-width: 14%; max-width: 14%; }  /* Profesión */
.parte-diario .lista-personas td:nth-child(9) { width: 9%; min-width: 9%; max-width: 9%; }   /* Objeto */
.parte-diario .lista-personas td:nth-child(10) { width: 3%; min-width: 3%; max-width: 3%; }  /* N° Pieza */
.parte-diario .lista-personas td:nth-child(11) { width: 8%; min-width: 8%; max-width: 8%; }  /* Procedencia */
.parte-diario .lista-personas td:nth-child(12) { width: 8%; min-width: 8%; max-width: 8%; }  /* Prov. Destino */
.parte-diario .lista-personas td:nth-child(13) { width: 4%; min-width: 4%; max-width: 4%; }  /* Vía */
.parte-diario .lista-personas td:nth-child(14) { width: 3%; min-width: 3%; max-width: 3%; }  /* Días */

/* Aplicar anchos también a las filas de encabezados de sección */
.parte-diario .fecha-row td:nth-child(1),
.parte-diario .ingresantes-header td:nth-child(1),
.parte-diario .pernoctantes-header td:nth-child(1),
.parte-diario .salientes-header td:nth-child(1),
.parte-diario .sin-novedad td:nth-child(1) {
    width: 100%;
}

.parte-diario td.left {
    text-align: left;
}

.parte-diario td.small {
    font-size: 9px;
}

body:not(.dark *) .parte-diario {
    background: white;
    color: black;
}

.dark .parte-diario {
    background: white;
    color: black;
}
</style>

<!-- Controles (no se imprimen) -->
<div class="no-print mb-6 sm:mb-8">
    <div class="flex flex-col gap-3 mb-4 sm:mb-6">
        <div class="flex-1">
            <h1 class="text-xl sm:text-2xl md:text-4xl font-bold text-noir dark:text-white mb-1 sm:mb-2">Parte Diario - Planilla de Pasajeros</h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Registro oficial mensual</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <a href="<?php echo BASE_PATH; ?>/index.php" class="px-4 py-2.5 sm:px-4 sm:py-2 text-sm sm:text-base text-center border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                ← Volver
            </a>
            <button onclick="window.print()" class="px-4 py-2.5 sm:px-6 sm:py-2 text-sm sm:text-base bg-noir dark:bg-white text-white dark:text-noir rounded-lg sm:rounded-xl font-medium hover:opacity-90 transition-all duration-200 shadow-md sm:shadow-lg flex items-center justify-center gap-2">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Imprimir
            </button>
        </div>
    </div>
    
    <!-- Selector de Mes/Año -->
    <div class="bg-white dark:bg-gray-900 rounded-lg sm:rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-800 mb-4 sm:mb-6">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-end gap-3 sm:gap-4">
            <div class="flex-1">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">Mes:</label>
                <select name="mes" class="w-full px-3 py-2 sm:px-4 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-white">
                    <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $mes ? 'selected' : ''; ?>>
                            <?php echo $meses[$m]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="flex-1">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">Año:</label>
                <select name="anio" class="w-full px-3 py-2 sm:px-4 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-white">
                    <?php for($a = date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php echo $a == $anio ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="flex-1">
                <button type="submit" class="w-full px-4 py-2 sm:px-6 text-sm sm:text-base bg-noir dark:bg-gray-800 text-white rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                    Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Planilla para Imprimir -->
<div class="parte-diario">
    <!-- Encabezado -->
    <table>
        <tr>
            <td colspan="3" style="text-align: left; font-weight: bold; padding: 8px;">
                Establecimiento: HOTEL CECIL<br>
                Dirección: Av. Ostria Gutierrez #106
            </td>
            <td colspan="3" style="text-align: center; font-weight: bold; padding: 8px;">
                Días: 1-<?php echo $ultimo_dia; ?>
            </td>
            <td colspan="3" style="text-align: center; font-weight: bold; padding: 8px;">
                MES: <?php echo $nombre_mes; ?>
            </td>
            <td colspan="5" style="text-align: right; font-weight: bold; padding: 8px;">
                Categoría: TRES ESTRELLAS<br>
                Teléfonos: 64-24658<br>
                Año: <?php echo $anio; ?>
            </td>
        </tr>
    </table>
    
    <!-- Cabecera de columnas -->
    <table>
        <colgroup>
            <col style="width: 4%;">
            <col style="width: 20%;">
            <col style="width: 3%;">
            <col style="width: 3%;">
            <col style="width: 3%;">
            <col style="width: 8%;">
            <col style="width: 10%;">
            <col style="width: 14%;">
            <col style="width: 9%;">
            <col style="width: 3%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 4%;">
            <col style="width: 3%;">
        </colgroup>
        <thead>
            <tr style="background-color: #4472C4; color: #000000; font-weight: bold;">
                <th style="width: 4%;">N°</th>
                <th style="width: 20%;">Nombres y Apellidos</th>
                <th style="width: 3%;">Genero</th>
                <th style="width: 3%;">Edad</th>
                <th style="width: 3%;">E.C.</th>
                <th style="width: 8%;">Nacionalidad</th>
                <th style="width: 10%;">C.I. / Pasaporte</th>
                <th style="width: 14%;">Profesion</th>
                <th style="width: 9%;">Objeto</th>
                <th style="width: 3%;">N° Pieza</th>
                <th style="width: 8%;">Procedencia</th>
                <th style="width: 8%;">Prov. Destino</th>
                <th style="width: 4%;">Via</th>
                <th style="width: 3%;">Días</th>
            </tr>
        </thead>
    </table>
    
    <!-- Contenido por días -->
    <?php 
    // Contadores independientes por sección (reinician en cada grupo del día)
    $cnt_ing = $cnt_per = $cnt_sal = 1;
    for($dia = 1; $dia <= $ultimo_dia; $dia++): 
        $fecha_completa = date('Y-m-d', strtotime("$anio-$mes-$dia"));
        $dia_semana = date('w', strtotime($fecha_completa));
        $tiene_ingresantes = isset($ingresantes_por_dia[$dia]);
        $tiene_pernoctantes = isset($pernoctantes_por_dia[$dia]);
        $tiene_salientes = isset($salientes_por_dia[$dia]);
        $tiene_registros = $tiene_ingresantes || $tiene_pernoctantes || $tiene_salientes;
    ?>
    
    <table>
        <colgroup>
            <col style="width: 4%;">
            <col style="width: 20%;">
            <col style="width: 3%;">
            <col style="width: 3%;">
            <col style="width: 3%;">
            <col style="width: 8%;">
            <col style="width: 10%;">
            <col style="width: 14%;">
            <col style="width: 9%;">
            <col style="width: 3%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 4%;">
            <col style="width: 3%;">
        </colgroup>
        <!-- Fecha del día -->
        <tr class="fecha-row">
            <td colspan="14" style="font-weight: bold; text-align: center; padding: 6px;">
                <?php echo $dia; ?> DE <?php echo $nombre_mes; ?>
            </td>
        </tr>
        
        <?php if ($tiene_registros): ?>
            
            <!-- INGRESANTES -->
            <?php if ($tiene_ingresantes): $cnt_ing = 1; ?>
            <tr class="ingresantes-header">
                <td colspan="14" style="text-align: center; padding: 4px;">
                    INGRESANTES
                </td>
            </tr>
            <?php foreach($ingresantes_por_dia[$dia] as $ocu): ?>
            <tr class="lista-personas">
                <td><?php echo $cnt_ing++; ?></td>
                <td class="left"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></td>
                <td><?php echo $ocu['genero']; ?></td>
                <td><?php echo $ocu['edad']; ?></td>
                <td class="small"><?php echo $ocu['estado_civil'] ? substr($ocu['estado_civil'], 0, 1) : 'S'; ?></td>
                <td class="small"><?php echo strtoupper(substr($ocu['nacionalidad'], 0, 8)); ?></td>
                <td class="small"><?php echo htmlspecialchars($ocu['ci_pasaporte']); ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['profesion'], 0, 14)); ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['objeto'], 0, 10)); ?></td>
                <td><?php echo $ocu['nro_pieza']; ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['procedencia'], 0, 10)); ?></td>
                <td class="small"><?php echo $ocu['prox_destino'] ? htmlspecialchars(substr($ocu['prox_destino'], 0, 10)) : 'Potosi'; ?></td>
                <td class="small"><?php echo $ocu['via_ingreso'] ? substr($ocu['via_ingreso'], 0, 1) : 'T'; ?></td>
                <td><?php echo $ocu['nro_dias']; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- PERNOCTANTES -->
            <?php if ($tiene_pernoctantes): $cnt_per = 1; ?>
            <tr class="pernoctantes-header">
                <td colspan="14" style="text-align: center; padding: 4px;">
                    PERNOCTANTES
                </td>
            </tr>
            <?php foreach($pernoctantes_por_dia[$dia] as $ocu): ?>
            <tr class="lista-personas">
                <td><?php echo $cnt_per++; ?></td>
                <td class="left"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></td>
                <td><?php echo $ocu['genero']; ?></td>
                <td><?php echo $ocu['edad']; ?></td>
                <td class="small"><?php echo $ocu['estado_civil'] ? substr($ocu['estado_civil'], 0, 1) : 'S'; ?></td>
                <td class="small"><?php echo strtoupper(substr($ocu['nacionalidad'], 0, 8)); ?></td>
                <td class="small"><?php echo htmlspecialchars($ocu['ci_pasaporte']); ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['profesion'], 0, 14)); ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['objeto'], 0, 10)); ?></td>
                <td><?php echo $ocu['nro_pieza']; ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['procedencia'], 0, 10)); ?></td>
                <td class="small"><?php echo $ocu['prox_destino'] ? htmlspecialchars(substr($ocu['prox_destino'], 0, 10)) : 'Potosi'; ?></td>
                <td class="small"><?php echo $ocu['via_ingreso'] ? substr($ocu['via_ingreso'], 0, 1) : 'T'; ?></td>
                <td><?php echo $ocu['nro_dias']; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- SALIENTES -->
            <?php if ($tiene_salientes): $cnt_sal = 1; ?>
            <tr class="salientes-header">
                <td colspan="14" style="text-align: center; padding: 4px;">
                    SALIENTES
                </td>
            </tr>
            <?php foreach($salientes_por_dia[$dia] as $ocu): ?>
            <tr class="lista-personas">
                <td><?php echo $cnt_sal++; ?></td>
                <td class="left"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></td>
                <td><?php echo $ocu['genero']; ?></td>
                <td><?php echo $ocu['edad']; ?></td>
                <td class="small"><?php echo $ocu['estado_civil'] ? substr($ocu['estado_civil'], 0, 1) : 'S'; ?></td>
                <td class="small"><?php echo strtoupper(substr($ocu['nacionalidad'], 0, 8)); ?></td>
                <td class="small"><?php echo htmlspecialchars($ocu['ci_pasaporte']); ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['profesion'], 0, 14)); ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['objeto'], 0, 10)); ?></td>
                <td><?php echo $ocu['nro_pieza']; ?></td>
                <td class="small"><?php echo htmlspecialchars(substr($ocu['procedencia'], 0, 10)); ?></td>
                <td class="small"><?php echo $ocu['prox_destino'] ? htmlspecialchars(substr($ocu['prox_destino'], 0, 10)) : 'Potosi'; ?></td>
                <td class="small"><?php echo $ocu['via_ingreso'] ? substr($ocu['via_ingreso'], 0, 1) : 'T'; ?></td>
                <td><?php echo $ocu['nro_dias']; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- SIN NOVEDAD -->
            <tr class="sin-novedad">
                <td colspan="14" style="padding: 8px;">
                    Sin novedad
                </td>
            </tr>
        <?php endif; ?>
    </table>
    
    <?php endfor; ?>
    
    <!-- Pie de página -->
    <div style="margin-top: 30px; page-break-inside: avoid;">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="width: 50%; text-align: center; border: none; padding: 20px;">
                    <div style="border-top: 1px solid #000; width: 60%; margin: 0 auto; padding-top: 5px;">
                        FIRMA Y SELLO DEL PROPIETARIO<br>
                        O ADMINISTRADOR
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none; padding: 20px;">
                    <div style="border-top: 1px solid #000; width: 60%; margin: 0 auto; padding-top: 5px;">
                        AUTORIDAD COMPETENTE<br>
                        MIGRACION / POLICIA
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
