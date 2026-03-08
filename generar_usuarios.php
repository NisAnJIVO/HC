<?php
// Script de utilidad — acceso bloqueado en producción
die('⛔ Acceso no permitido. Este script ya fue ejecutado y debe eliminarse.');


$conn = getConnection();
$resultados = [];

// Usuario 1: Isaac Vargas - actualizar contraseña (ya existe id=2)
$hash_isaac = password_hash('nisan123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE usuarios SET password = :pwd WHERE usuario = 'Isaac Vargas'");
$stmt->execute([':pwd' => $hash_isaac]);
$resultados[] = "Isaac Vargas actualizado. Hash: " . $hash_isaac;

// Usuario 2: Gabriel Durán - insertar si no existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = 'Gabriel Durán'");
$stmt->execute();
if (!$stmt->fetch()) {
    $hash_gabriel = password_hash('gabon123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, nombre_completo, rol, activo) VALUES ('Gabriel Durán', :pwd, 'Gabriel Durán', 'administrador', 1)");
    $stmt->execute([':pwd' => $hash_gabriel]);
    $resultados[] = "Gabriel Durán insertado. Hash: " . $hash_gabriel;
} else {
    $hash_gabriel = password_hash('gabon123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE usuarios SET password = :pwd WHERE usuario = 'Gabriel Durán'");
    $stmt->execute([':pwd' => $hash_gabriel]);
    $resultados[] = "Gabriel Durán ya existía, contraseña actualizada.";
}

// Verificar
$stmt = $conn->prepare("SELECT id, usuario, nombre_completo, rol, activo FROM usuarios ORDER BY id");
$stmt->execute();
$usuarios = $stmt->fetchAll();

echo "<pre>";
foreach ($resultados as $r) echo $r . "\n";
echo "\n--- Usuarios actuales ---\n";
foreach ($usuarios as $u) {
    echo "ID:{$u['id']} | {$u['usuario']} | {$u['nombre_completo']} | {$u['rol']} | activo:{$u['activo']}\n";
}
echo "</pre>";
echo "<p style='color:green'><b>Listo. Podés eliminar este archivo ahora.</b></p>";
?>
