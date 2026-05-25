<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Planilla de Huéspedes';

$conn = getConnection();
$PER_PAGE = 20;

$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin    = $_GET['fecha_fin']    ?? null;
$buscar       = trim($_GET['buscar']  ?? '');

$filtrando = isset($_GET['filtrar']) || $buscar !== '' || ($fecha_inicio && $fecha_fin);

$camposSelect = "SELECT ro.id, ro.huesped_id, ro.habitacion_id, ro.nro_pieza, ro.prox_destino,
        ro.via_ingreso, ro.fecha_ingreso, ro.nro_dias, ro.fecha_salida_estimada, ro.fecha_salida_real,
        ro.estado,
        h.nombres_apellidos, h.ci_pasaporte, h.genero, h.edad,
        h.estado_civil, h.nacionalidad, h.profesion, h.objeto, h.procedencia
        FROM registro_ocupacion ro
        INNER JOIN huespedes h ON ro.huesped_id = h.id";

// Construir cláusulas WHERE dinámicas
$whereClauses = [];
$queryParams = [];

if ($fecha_inicio && $fecha_fin) {
    $whereClauses[] = "ro.fecha_ingreso BETWEEN :fi AND :ff";
    $queryParams[':fi'] = $fecha_inicio;
    $queryParams[':ff'] = $fecha_fin;
}

if ($buscar !== '') {
    $whereClauses[] = "(h.nombres_apellidos LIKE :b1 OR h.ci_pasaporte LIKE :b2 OR ro.nro_pieza LIKE :b3)";
    $queryParams[':b1'] = '%' . $buscar . '%';
    $queryParams[':b2'] = '%' . $buscar . '%';
    $queryParams[':b3'] = '%' . $buscar . '%';
}

$where = "";
if (count($whereClauses) > 0) {
    $where = " WHERE " . implode(" AND ", $whereClauses);
}

// 1. Obtener conteo total con los filtros aplicados
$sqlCount = "SELECT COUNT(*) " . substr($camposSelect, strpos($camposSelect, 'FROM')) . $where;
$stmtCount = $conn->prepare($sqlCount);
foreach ($queryParams as $key => $val) {
    $stmtCount->bindValue($key, $val);
}
$stmtCount->execute();
$total = (int)$stmtCount->fetchColumn();

// 2. Calcular paginación
$totalPaginas = max(1, ceil($total / $PER_PAGE));
$pagina = isset($_GET['pagina']) ? max(1, min((int)$_GET['pagina'], $totalPaginas)) : $totalPaginas;
$offset = ($pagina - 1) * $PER_PAGE;

// 3. Obtener los registros paginados y filtrados
$sqlData = $camposSelect . $where . " ORDER BY ro.id ASC LIMIT :lim OFFSET :off";
$stmtData = $conn->prepare($sqlData);
foreach ($queryParams as $key => $val) {
    $stmtData->bindValue($key, $val);
}
$stmtData->bindValue(':lim', $PER_PAGE, PDO::PARAM_INT);
$stmtData->bindValue(':off', $offset,   PDO::PARAM_INT);
$stmtData->execute();
$registros = $stmtData->fetchAll();

// Helper para construir URLs de paginación conservando los filtros activos
function paginaUrl(int $p): string {
    $params = $_GET;
    $params['pagina'] = $p;
    return '?' . http_build_query($params);
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
    body { background: #f4f4f5; }
    .dark body { background: #080808; }

    /* Contenedor Apple Clean */
    .apple-card-clean {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 20px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.02);
        padding: 24px;
    }
    .dark .apple-card-clean {
        background: #121212;
        border-color: rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    }

    /* Inputs Apple Style */
    .input-apple {
        width: 100%;
        background: #f5f5f7;
        border: 1px solid rgba(0, 0, 0, 0.02);
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #1c1c1e;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        outline: none;
    }
    .input-apple-search {
        padding-left: 38px;
    }
    .input-apple:focus {
        background: #ffffff;
        border-color: #007aff;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
    }
    .dark .input-apple {
        background: rgba(255, 255, 255, 0.04);
        border-color: transparent;
        color: #ffffff;
    }
    .dark .input-apple:focus {
        background: #1c1c1e;
        border-color: #0a84ff;
        box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.2);
    }

    /* Apple Pill Buttons */
    .btn-apple {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        border: 1px solid transparent;
    }
    .btn-apple:active {
        transform: scale(0.97);
    }

    .btn-apple-primary {
        background: #1c1c1e;
        color: #ffffff;
    }
    .btn-apple-primary:hover {
        background: #2c2c2e;
    }
    .dark .btn-apple-primary {
        background: #ffffff;
        color: #1c1c1e;
    }
    .dark .btn-apple-primary:hover {
        background: #f5f5f7;
    }

    .btn-apple-secondary {
        background: rgba(0, 0, 0, 0.04);
        color: #1c1c1e;
        border-color: rgba(0, 0, 0, 0.02);
    }
    .btn-apple-secondary:hover {
        background: rgba(0, 0, 0, 0.08);
    }
    .dark .btn-apple-secondary {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.02);
    }
    .dark .btn-apple-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .btn-apple-green {
        background: rgba(52, 199, 89, 0.1);
        color: #24b23e;
    }
    .btn-apple-green:hover {
        background: rgba(52, 199, 89, 0.16);
    }
    .dark .btn-apple-green {
        background: rgba(48, 209, 88, 0.15);
        color: #30d158;
    }
    .dark .btn-apple-green:hover {
        background: rgba(48, 209, 88, 0.22);
    }

    /* Paginación */
    .pagination-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 550;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.04);
        color: #1c1c1e;
        transition: background 0.15s, color 0.15s;
    }
    .pagination-btn:hover {
        background: rgba(0, 0, 0, 0.08);
    }
    .pagination-btn.active {
        background: #1c1c1e;
        color: #ffffff;
        font-weight: 600;
    }
    .dark .pagination-btn {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }
    .dark .pagination-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    .dark .pagination-btn.active {
        background: #ffffff;
        color: #1c1c1e;
    }

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
        
        @page {
            size: legal landscape;
            margin: 0.5cm;
            margin-top: 0.5cm;
            margin-bottom: 0.5cm;
        }
        
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
            border-color: rgba(255,255,255,0.06) !important;
        }
        
        .print-table {
            font-size: 10px;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .dark .print-table {
            border-color: rgba(255, 255, 255, 0.05);
        }
        
        @media (min-width: 640px) {
            .print-table {
                font-size: 11px;
            }
        }
        
        .print-table th,
        .print-table td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            border-right: 1px solid rgba(0, 0, 0, 0.04);
            white-space: nowrap;
        }
        .dark .print-table th,
        .dark .print-table td {
            border-bottom-color: rgba(255, 255, 255, 0.04);
            border-right-color: rgba(255, 255, 255, 0.04);
        }
        
        .print-table thead {
            background-color: #f5f5f7;
            color: #1c1c1e;
            font-weight: 600;
        }
        .dark .print-table thead {
            background-color: #1c1c1e;
            color: #f5f5f7;
        }
    }
</style>

<!-- Cabecera minimalista superior -->
<div class="no-print flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
    <div>
        <p class="text-[11px] tracking-widest uppercase text-gray-400 font-semibold mb-1">Reportes Oficiales</p>
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Planilla de Huéspedes</h1>
    </div>
    <div class="flex flex-wrap gap-2.5">
        <a href="<?php echo BASE_PATH; ?>/index.php" class="btn-apple btn-apple-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <button onclick="window.print()" class="btn-apple btn-apple-primary shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimir / PDF
        </button>
    </div>
</div>

<!-- Controles de filtro en tarjeta Apple Clean -->
<div class="no-print mb-8">
    <div class="apple-card-clean">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <!-- Resetear página al filtrar -->
            <input type="hidden" name="pagina" value="1">
            
            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Búsqueda rápida</label>
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text" name="buscar" placeholder="Nombre, CI o pieza..."
                           value="<?php echo htmlspecialchars($buscar); ?>"
                           class="input-apple input-apple-search">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" 
                       value="<?php echo htmlspecialchars($fecha_inicio ?? ''); ?>"
                       class="input-apple">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Fecha Fin</label>
                <input type="date" name="fecha_fin" 
                       value="<?php echo htmlspecialchars($fecha_fin ?? ''); ?>"
                       class="input-apple">
            </div>

            <div class="flex gap-2">
                <button type="submit" name="filtrar" class="flex-1 btn-apple btn-apple-primary">
                    Filtrar
                </button>
                <?php if ($filtrando): ?>
                <a href="?" class="btn-apple btn-apple-secondary text-center px-4">
                    Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Planilla para imprimir -->
<div class="planilla-print bg-white dark:bg-zinc-900 md:rounded-2xl p-5 md:p-6 border border-gray-100 dark:border-zinc-800 shadow-sm md:shadow-md">
    <!-- Encabezado de la planilla -->
    <div class="mb-6 pb-4 border-b-2 border-gray-900 dark:border-white text-center">
        <h2 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">HOTEL CECIL</h2>
        <h4 class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 mt-1 font-semibold">Planilla Oficial de Registro de Huéspedes</h4>
    </div>
    
    <!-- Tabla de registros -->
    <div class="w-full overflow-x-auto -webkit-overflow-scrolling: touch;">
        <table class="print-table w-full text-left min-width: 1200px;">
            <thead>
                <tr>
                    <th class="text-center font-bold">Nro</th>
                    <th class="font-bold">Nombres y Apellidos</th>
                    <th class="text-center font-bold">G</th>
                    <th class="text-center font-bold">Edad</th>
                    <th class="text-center font-bold">E.C.</th>
                    <th class="font-bold">Nacionalidad</th>
                    <th class="font-bold">C.I./Pasaporte</th>
                    <th class="font-bold">Profesión</th>
                    <th class="font-bold">Objeto</th>
                    <th class="text-center font-bold">Pieza</th>
                    <th class="font-bold">Procedencia</th>
                    <th class="font-bold">Próx. Destino</th>
                    <th class="text-center font-bold">Vía</th>
                    <th class="text-center font-bold">F. Ingreso</th>
                    <th class="text-center font-bold">Días</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="15" class="text-center py-10 text-gray-400 font-medium">
                            No se encontraron registros de huéspedes con los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $idx => $reg): ?>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                            <td class="text-center font-mono text-gray-400"><?php echo $offset + $idx + 1; ?></td>
                            <td class="font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($reg['nombres_apellidos']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($reg['genero']); ?></td>
                            <td class="text-center font-mono"><?php echo (int)$reg['edad']; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($reg['estado_civil'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($reg['nacionalidad']); ?></td>
                            <td class="font-mono text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($reg['ci_pasaporte']); ?></td>
                            <td><?php echo htmlspecialchars($reg['profesion'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($reg['objeto'] ?? '-'); ?></td>
                            <td class="text-center font-bold font-mono text-gray-800 dark:text-gray-100"><?php echo htmlspecialchars($reg['nro_pieza']); ?></td>
                            <td><?php echo htmlspecialchars($reg['procedencia'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($reg['prox_destino'] ?? '-'); ?></td>
                            <td class="text-center font-bold text-[10px]"><?php echo $reg['via_ingreso'] ? strtoupper(htmlspecialchars($reg['via_ingreso'])) : '-'; ?></td>
                            <td class="text-center font-mono text-xs"><?php echo formatDate($reg['fecha_ingreso']); ?></td>
                            <td class="text-center font-mono font-bold"><?php echo (int)$reg['nro_dias']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Footer con totales -->
    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800 flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Total de registros encontrados: <span class="font-mono text-lg font-bold ml-1"><?php echo $total; ?></span></p>
        <p class="text-[10px] text-gray-400 tracking-wider uppercase font-semibold">
            Sistema de Gestión Hotel Cecil
        </p>
    </div>
</div>

<!-- Controles de paginación -->
<div class="no-print mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-400 font-semibold uppercase tracking-wider">
    <div>
        Mostrando <strong class="text-gray-700 dark:text-gray-200 font-mono"><?php echo count($registros); ?></strong> de <strong class="text-gray-700 dark:text-gray-200 font-mono"><?php echo $total; ?></strong> registros
        &nbsp;·&nbsp; Página <strong class="text-gray-700 dark:text-gray-200 font-mono"><?php echo $pagina; ?></strong> de <strong class="text-gray-700 dark:text-gray-200 font-mono"><?php echo $totalPaginas; ?></strong>
    </div>
    <div class="flex items-center gap-1.5 normal-case tracking-normal">
        <?php if ($pagina > 1): ?>
            <a href="<?php echo paginaUrl(1); ?>" class="pagination-btn">«</a>
            <a href="<?php echo paginaUrl($pagina - 1); ?>" class="pagination-btn">Anterior</a>
        <?php else: ?>
            <span class="pagination-btn opacity-40 cursor-not-allowed">«</span>
            <span class="pagination-btn opacity-40 cursor-not-allowed">Anterior</span>
        <?php endif; ?>

        <?php
        $inicio = max(1, $pagina - 2);
        $fin    = min($totalPaginas, $pagina + 2);
        for ($i = $inicio; $i <= $fin; $i++):
        ?>
            <?php if ($i === $pagina): ?>
                <span class="pagination-btn active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo paginaUrl($i); ?>" class="pagination-btn"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?php echo paginaUrl($pagina + 1); ?>" class="pagination-btn">Siguiente</a>
            <a href="<?php echo paginaUrl($totalPaginas); ?>" class="pagination-btn">»</a>
        <?php else: ?>
            <span class="pagination-btn opacity-40 cursor-not-allowed">Siguiente</span>
            <span class="pagination-btn opacity-40 cursor-not-allowed">»</span>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

