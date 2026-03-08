<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Planilla de Huéspedes';

$conn = getConnection();
$PER_PAGE = 20;

$filtrando = isset($_GET['filtrar']);
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin    = $_GET['fecha_fin']    ?? null;

$camposSelect = "SELECT ro.id, ro.huesped_id, ro.habitacion_id, ro.nro_pieza, ro.prox_destino,
        ro.via_ingreso, ro.fecha_ingreso, ro.nro_dias, ro.fecha_salida_estimada, ro.fecha_salida_real,
        ro.estado,
        h.nombres_apellidos, h.ci_pasaporte, h.genero, h.edad,
        h.estado_civil, h.nacionalidad, h.profesion, h.objeto, h.procedencia
        FROM registro_ocupacion ro
        INNER JOIN huespedes h ON ro.huesped_id = h.id";

if ($filtrando && $fecha_inicio && $fecha_fin) {
    $where = " WHERE ro.fecha_ingreso BETWEEN :fi AND :ff";

    $stmtCount = $conn->prepare("SELECT COUNT(*) " . substr($camposSelect, strpos($camposSelect, 'FROM')) . $where);
    $stmtCount->execute([':fi' => $fecha_inicio, ':ff' => $fecha_fin]);
    $total = (int)$stmtCount->fetchColumn();
} else {
    $fecha_inicio = null;
    $fecha_fin    = null;

    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM registro_ocupacion");
    $stmtCount->execute();
    $total = (int)$stmtCount->fetchColumn();
}

// Total de páginas se calcula ANTES de saber la página actual
// para que el default sea siempre la última
$totalPaginas = max(1, ceil($total / $PER_PAGE));
$pagina = isset($_GET['pagina']) ? max(1, min((int)$_GET['pagina'], $totalPaginas)) : $totalPaginas;
$offset = ($pagina - 1) * $PER_PAGE;

if ($filtrando && $fecha_inicio && $fecha_fin) {
    $where = " WHERE ro.fecha_ingreso BETWEEN :fi AND :ff";
    $stmtData = $conn->prepare($camposSelect . $where . " ORDER BY ro.id ASC LIMIT :lim OFFSET :off");
    $stmtData->bindValue(':fi',  $fecha_inicio);
    $stmtData->bindValue(':ff',  $fecha_fin);
    $stmtData->bindValue(':lim', $PER_PAGE, PDO::PARAM_INT);
    $stmtData->bindValue(':off', $offset,   PDO::PARAM_INT);
    $stmtData->execute();
    $registros = $stmtData->fetchAll();
} else {
    $stmtData = $conn->prepare($camposSelect . " ORDER BY ro.id ASC LIMIT :lim OFFSET :off");
    $stmtData->bindValue(':lim', $PER_PAGE, PDO::PARAM_INT);
    $stmtData->bindValue(':off', $offset,   PDO::PARAM_INT);
    $stmtData->execute();
    $registros = $stmtData->fetchAll();
}

// Helper para construir URLs de paginación conservando los filtros activos
function paginaUrl(int $p): string {
    $params = $_GET;
    $params['pagina'] = $p;
    return '?' . http_build_query($params);
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
    /* Estilos para impresión */
    @media print {
        body * {
            visibility: hidden;
        }
        .planilla-print, .planilla-print * {
            visibility: visible;
        }
        .planilla-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        
        /* Configuración de página para oficio (8.5" x 13") */
        /* Eliminar encabezados y pies de página del navegador */
        @page {
            size: legal landscape;
            margin: 0.5cm;
            margin-top: 0.5cm;
            margin-bottom: 0.5cm;
        }
        
        /* Ocultar encabezado y pie de página del navegador */
        html {
            margin: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        .planilla-print {
            padding: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px !important;
        }
        
        th, td {
            border: 1px solid #000 !important;
            padding: 3px !important;
        }
        
        thead {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        h2, h4 {
            margin: 5px 0 !important;
        }
    }
    
    /* Estilos en pantalla */
    @media screen {
        .planilla-print {
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .dark .planilla-print * {
            color: white !important;
        }
        
        .dark .planilla-print td,
        .dark .planilla-print th {
            border-color: #666 !important;
        }
        
        .print-table {
            font-size: 10px;
        }
        
        @media (min-width: 640px) {
            .print-table {
                font-size: 11px;
            }
        }
        
        .print-table th,
        .print-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            white-space: nowrap;
        }
        
        @media (min-width: 640px) {
            .print-table th,
            .print-table td {
                padding: 6px 8px;
            }
        }
        
        .print-table thead {
            background-color: #2c3e50;
            color: white;
        }
    }
</style>

<!-- Controles de filtro -->
<div class="no-print mb-6 sm:mb-8">
    <div class="bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-200 dark:border-gray-800 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-noir dark:text-white">Planilla de Huéspedes</h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Registro oficial de ocupaciones</p>
                </div>
                <div class="flex gap-2 sm:gap-3">
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="flex-1 sm:flex-initial px-3 py-2 sm:px-4 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-center">
                        ← Volver
                    </a>
                    <button onclick="exportarCSV()" class="flex-1 sm:flex-initial px-3 py-2 sm:px-4 text-sm sm:text-base border border-green-600 text-green-700 dark:text-green-400 dark:border-green-700 font-medium rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        CSV
                    </button>
                    <button onclick="window.print()" class="flex-1 sm:flex-initial px-3 py-2 sm:px-6 text-sm sm:text-base bg-noir dark:bg-gray-800 text-white font-semibold rounded-lg hover:bg-gray-800 dark:hover:bg-gray-700 transition-all shadow-md sm:shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span class="hidden sm:inline">Imprimir / PDF</span>
                        <span class="sm:hidden">PDF</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-4 sm:p-6">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                <!-- Resetear página al filtrar -->
                <input type="hidden" name="pagina" value="1">
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" 
                           value="<?php echo htmlspecialchars($fecha_inicio ?? ''); ?>"
                           class="w-full px-3 py-2 sm:px-4 sm:py-2.5 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-noir focus:border-transparent bg-white dark:bg-gray-800 text-noir dark:text-white">
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" 
                           value="<?php echo htmlspecialchars($fecha_fin ?? ''); ?>"
                           class="w-full px-3 py-2 sm:px-4 sm:py-2.5 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-noir focus:border-transparent bg-white dark:bg-gray-800 text-noir dark:text-white">
                </div>
                <div class="sm:flex sm:items-end gap-2">
                    <button type="submit" name="filtrar" class="w-full px-4 py-2 sm:py-2.5 text-sm sm:text-base bg-noir dark:bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-700 transition-all">
                        Filtrar
                    </button>
                    <?php if ($filtrando): ?>
                    <a href="?" class="mt-2 sm:mt-0 block w-full text-center px-4 py-2 sm:py-2.5 text-sm sm:text-base border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        Limpiar
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            <!-- Búsqueda instantánea -->
            <div class="mt-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input type="text" id="busqueda-planilla" placeholder="Buscar por nombre, CI o pieza..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-noir focus:border-transparent bg-white dark:bg-gray-800 text-noir dark:text-white"
                           oninput="filtrarTabla(this.value)">
                </div>
                <p id="contador-busqueda" class="text-xs text-gray-400 mt-1 hidden"></p>
            </div>
        </div>
    </div>
</div>

<!-- Planilla para imprimir -->
<div class="planilla-print">
    <!-- Encabezado de la planilla -->
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
        <h2 style="margin: 5px 0; font-size: 20px; font-weight: bold;">HOTEL CECIL</h2>
        <h4 style="margin: 5px 0; font-size: 14px;">PLANILLA DE REGISTRO DE HUÉSPEDES</h4>
    </div>
    
    <!-- Tabla de registros -->
    <div style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="print-table" style="width: 100%; border-collapse: collapse; min-width: 1200px;">
            <thead>
                <tr>
                    <th style="text-align: center; font-weight: bold;">Nro</th>
                    <th style="text-align: left; font-weight: bold;">Nombres y Apellidos</th>
                    <th style="text-align: center; font-weight: bold;">G</th>
                    <th style="text-align: center; font-weight: bold;">Edad</th>
                    <th style="text-align: center; font-weight: bold;">E.C.</th>
                    <th style="text-align: left; font-weight: bold;">Nacionalidad</th>
                    <th style="text-align: left; font-weight: bold;">C.I./Pasaporte</th>
                    <th style="text-align: left; font-weight: bold;">Profesión</th>
                    <th style="text-align: left; font-weight: bold;">Objeto</th>
                    <th style="text-align: center; font-weight: bold;">Pieza</th>
                    <th style="text-align: left; font-weight: bold;">Procedencia</th>
                    <th style="text-align: left; font-weight: bold;">Próx. Destino</th>
                    <th style="text-align: center; font-weight: bold;">Vía</th>
                    <th style="text-align: center; font-weight: bold;">F. Ingreso</th>
                    <th style="text-align: center; font-weight: bold;">Días</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="15" style="text-align: center; padding: 20px; color: #666;">
                            No hay registros para mostrar en el período seleccionado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $idx => $reg): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $idx + 1; ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['nombres_apellidos']); ?></td>
                            <td style="text-align: center;"><?php echo $reg['genero']; ?></td>
                            <td style="text-align: center;"><?php echo $reg['edad']; ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($reg['estado_civil'] ?? '-'); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['nacionalidad']); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['ci_pasaporte']); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['profesion'] ?? '-'); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['objeto'] ?? '-'); ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $reg['nro_pieza']; ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['procedencia'] ?? '-'); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($reg['prox_destino'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo $reg['via_ingreso'] ? strtoupper($reg['via_ingreso']) : '-'; ?></td>
                            <td style="text-align: center;"><?php echo formatDate($reg['fecha_ingreso']); ?></td>
                            <td style="text-align: center;"><?php echo $reg['nro_dias']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Footer con totales -->
    <div style="margin-top: 20px; padding-top: 10px; border-top: 2px solid #000;">
        <p style="margin: 5px 0; font-weight: bold;">Total de registros: <?php echo $total; ?></p>
        <p style="margin: 5px 0; font-size: 10px; color: #666;">
            Documento generado electrónicamente por el Sistema de Gestión Hotel Cecil
        </p>
    </div>
</div>

<!-- Controles de paginación (no se imprimen) -->
<div class="no-print mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-600 dark:text-gray-400">
    <div>
        Mostrando <strong><?php echo count($registros); ?></strong> de <strong><?php echo $total; ?></strong> registros
        &nbsp;·&nbsp; Página <strong><?php echo $pagina; ?></strong> de <strong><?php echo $totalPaginas; ?></strong>
    </div>
    <div class="flex items-center gap-1">
        <?php if ($pagina > 1): ?>
            <a href="<?php echo paginaUrl(1); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">«</a>
            <a href="<?php echo paginaUrl($pagina - 1); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Anterior</a>
        <?php else: ?>
            <span class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 cursor-not-allowed">«</span>
            <span class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 cursor-not-allowed">Anterior</span>
        <?php endif; ?>

        <?php
        $inicio = max(1, $pagina - 2);
        $fin    = min($totalPaginas, $pagina + 2);
        for ($i = $inicio; $i <= $fin; $i++):
        ?>
            <?php if ($i === $pagina): ?>
                <span class="px-3 py-1.5 bg-gray-800 dark:bg-gray-600 text-white rounded-lg font-semibold"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo paginaUrl($i); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?php echo paginaUrl($pagina + 1); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Siguiente</a>
            <a href="<?php echo paginaUrl($totalPaginas); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">»</a>
        <?php else: ?>
            <span class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 cursor-not-allowed">Siguiente</span>
            <span class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 cursor-not-allowed">»</span>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
// ── Búsqueda instantánea ────────────────────────────────────────────────────
function filtrarTabla(query) {
    const q = query.trim().toLowerCase();
    const filas = document.querySelectorAll('table tbody tr');
    let visibles = 0;

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const mostrar = !q || texto.includes(q);
        fila.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });

    const contador = document.getElementById('contador-busqueda');
    if (q) {
        contador.textContent = `${visibles} resultado${visibles !== 1 ? 's' : ''} encontrado${visibles !== 1 ? 's' : ''}`;
        contador.classList.remove('hidden');
    } else {
        contador.classList.add('hidden');
    }
}

// ── Exportar CSV ─────────────────────────────────────────────────────────────
function exportarCSV() {
    const tabla = document.querySelector('table');
    if (!tabla) return;

    const filas = tabla.querySelectorAll('tr');
    const rows = [];

    filas.forEach(fila => {
        if (fila.style.display === 'none') return;
        const celdas = fila.querySelectorAll('th, td');
        const row = Array.from(celdas).map(c => {
            // Escapar comillas y encerrar en comillas si contiene coma
            let val = c.textContent.trim().replace(/"/g, '""');
            return val.includes(',') || val.includes('\n') ? `"${val}"` : val;
        });
        rows.push(row.join(','));
    });

    const csv = '\uFEFF' + rows.join('\n'); // BOM para Excel con UTF-8
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `planilla_huespedes_<?php echo date('Y-m-d'); ?>.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

