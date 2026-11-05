<?php
// Script de prueba y consulta a MariaDB
// Archivo: db_test.php

header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "empresa_user";
$pass = "PasswordSegura123!";
$db   = "empresa_db";

$mysqli = @new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Fallo de conexión: " . $mysqli->connect_error
    ]);
    exit;
}

// Crear tabla de demostración si no existe
$mysqli->query("CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    puesto VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insertar datos de prueba si está vacía
$res = $mysqli->query("SELECT COUNT(*) as total FROM empleados");
$row = $res->fetch_assoc();
if ($row['total'] == 0) {
    $mysqli->query("INSERT INTO empleados (nombre, puesto) VALUES 
        ('Juan Pérez', 'Administrador de Servidores'),
        ('María López', 'Especialista en Redes y Seguridad'),
        ('Carlos Gómez', 'Desarrollador Web')");
}

// Consultar registros
$resultado = $mysqli->query("SELECT id, nombre, puesto FROM empleados LIMIT 5");
$empleados = [];
while ($item = $resultado->fetch_assoc()) {
    $empleados[] = $item;
}

echo json_encode([
    "status" => "success",
    "motor_bd" => "MariaDB Server v" . $mysqli->server_info,
    "base_datos" => $db,
    "total_empleados" => count($empleados),
    "datos" => $empleados
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$mysqli->close();

