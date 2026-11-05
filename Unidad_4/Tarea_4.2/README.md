# Tarea 4.2: Implementación de Stack de Servicios Web (HTTPS) y Transferencia de Archivos (vsftpd)

Este repositorio contiene los archivos de configuración, páginas de prueba dinámicas, scripts de generación de certificados SSL y guías paso a paso para el despliegue del stack web LAMP y servidor de archivos seguro sobre **Debian Server**.

---

## Contenido de la Carpeta

| Archivo | Descripción |
| :--- | :--- |
| **`empresa.local.conf`** | Virtual Host HTTP en puerto 80 con redirección automática 301 a HTTPS. |
| **`empresa.local-ssl.conf`** | Virtual Host HTTPS en puerto 443 con certificados SSL/TLS, protocolos seguros y DocumentRoot. |
| **`generar_certificados.sh`** | Script bash con OpenSSL para generar certificados con Subject Alternative Names (SAN). |
| **`vsftpd.conf`** | Configuración de vsftpd: bloqueo anónimo, puertos pasivos, cifrado y enjaulamiento chroot. |
| **`index.php`** | Página corporativa interactiva que muestra estado HTTPS, versión de PHP y conexión a MariaDB. |
| **`db_test.php`** | Script de prueba de base de datos que consulta registros de la tabla de empleados en formato JSON. |
| **`MANUAL_PRUEBAS.md`** | Manual técnico detallado para la instalación, despliegue y pruebas de seguridad (chroot y HTTPS). |
| **`GUION_VIDEO.md`** | Guion narrativo detallado para la grabación del video en YouTube con cámara web obligatoria. |

