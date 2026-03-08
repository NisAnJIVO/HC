// Funciones principales del sistema

// Confirmar acciones
function confirmarAccion(mensaje) {
    return confirm(mensaje || '¿Está seguro de realizar esta acción?');
}

// Imprimir planilla
function imprimirPlanilla() {
    window.print();
}

// Buscar huésped por CI
function buscarHuespedPorCI() {
    const ci = document.getElementById('ci_buscar').value;
    if (ci.length < 3) return;
    
    fetch('/controllers/buscar_huesped.php?ci=' + encodeURIComponent(ci))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.huesped) {
                const h = data.huesped;
                document.getElementById('nombres_apellidos').value = h.nombres_apellidos;
                document.getElementById('genero').value = h.genero;
                document.getElementById('edad').value = h.edad;
                document.getElementById('estado_civil').value = h.estado_civil;
                document.getElementById('nacionalidad').value = h.nacionalidad;
                document.getElementById('profesion').value = h.profesion;
                document.getElementById('objeto').value = h.objeto;
                document.getElementById('procedencia').value = h.procedencia;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Calcular fecha de salida
function calcularFechaSalida() {
    const fechaIngreso = document.getElementById('fecha_ingreso');
    const nroDias = document.getElementById('nro_dias');
    const fechaSalida = document.getElementById('fecha_salida_estimada');
    
    if (fechaIngreso && nroDias && fechaSalida) {
        if (fechaIngreso.value && nroDias.value) {
            const ingreso = new Date(fechaIngreso.value);
            const dias = parseInt(nroDias.value);
            const salida = new Date(ingreso);
            salida.setDate(salida.getDate() + dias);
            fechaSalida.value = salida.toISOString().split('T')[0];
        }
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Autocompletar fecha actual en campos de fecha si están vacíos
    const fechaInputs = document.querySelectorAll('input[type="date"]');
    const hoy = new Date().toISOString().split('T')[0];
    
    fechaInputs.forEach(input => {
        if (!input.value && input.id !== 'fecha_salida_estimada') {
            input.value = hoy;
        }
    });
});
