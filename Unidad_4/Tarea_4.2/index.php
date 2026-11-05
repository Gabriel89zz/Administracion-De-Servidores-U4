<?php
// Página Web Dinámica Corporativa - Empresa.local
// Demuestra soporte dinámico PHP, cifrado HTTPS y conexión a MariaDB

$page_title = "Portal Corporativo Seguro | Empresa.local";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "HTTPS (Cifrado Activo)" : "HTTP (No Seguro)";
$server_ip = $_SERVER['SERVER_ADDR'] ?? '192.168.50.10';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
$php_version = phpversion();

// Verificación de conexión a base de datos MariaDB
$db_status = "Desconectado";
$db_class = "danger";
$db_details = "";

try {
    $mysqli = @new mysqli("localhost", "empresa_user", "PasswordSegura123!", "empresa_db");
    if ($mysqli->connect_error) {
        $db_status = "No disponible (" . $mysqli->connect_error . ")";
        $db_class = "warning";
    } else {
        $db_status = "Conectado exitosamente a MariaDB (v" . $mysqli->server_info . ")";
        $db_class = "success";
        $mysqli->close();
    }
} catch (Exception $e) {
    $db_status = "Error: " . $e->getMessage();
    $db_class = "warning";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #0d47a1, #1976d2); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        .card { background: #f8f9fa; border-left: 5px solid #1976d2; padding: 15px 20px; margin-bottom: 20px; border-radius: 4px; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 13px; }
        .badge.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge.danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background-color: #e3f2fd; color: #0d47a1; }
        .footer { background: #eceff1; padding: 15px 30px; text-align: center; font-size: 13px; color: #607d8b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Portal Corporativo Seguro</h1>
            <p>Servicio Web Apache2 + PHP + MariaDB bajo protocolo seguro HTTPS (SSL/TLS)</p>
        </div>
        <div class="content">
            <div class="card">
                <h3>Estado de Seguridad de la Conexión</h3>
                <p>Protocolo de acceso: <span class="badge success">🔒 <?php echo $protocol; ?></span></p>
                <p>Host solicitado: <strong><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'empresa.local'); ?></strong></p>
            </div>

            <h3>Detalles del Entorno del Servidor</h3>
            <table>
                <tr>
                    <th>Parámetro</th>
                    <th>Valor Detectado</th>
                </tr>
                <tr>
                    <td>Dirección IP del Servidor</td>
                    <td><code><?php echo $server_ip; ?></code></td>
                </tr>
                <tr>
                    <td>Dirección IP del Cliente</td>
                    <td><code><?php echo $client_ip; ?></code></td>
                </tr>
                <tr>
                    <td>Versión del Intérprete PHP</td>
                    <td><code>PHP v<?php echo $php_version; ?></code></td>
                </tr>
                <tr>
                    <td>Servidor Web</td>
                    <td><code><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4'); ?></code></td>
                </tr>
                <tr>
                    <td>Base de Datos (MariaDB)</td>
                    <td><span class="badge <?php echo $db_class; ?>"><?php echo $db_status; ?></span></td>
                </tr>
                <tr>
                    <td>Fecha y Hora del Servidor</td>
                    <td><?php echo date('Y-m-d H:i:s T'); ?></td>
                </tr>
            </table>
        </div>
        <div class="footer">
            Materia: Administración de Servidores | Unidad 4 - Tarea 4.2 | Debian GNU/Linux
        </div>
    </div>
</body>
</html>

