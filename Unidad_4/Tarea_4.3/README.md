# Tarea 4.3: Implementación y Aseguramiento de Servidor de Correo Electrónico (Postfix + Dovecot)

Este repositorio contiene los archivos de configuración, scripts de creación de buzones Maildir, manual de pruebas y guion para video demostrativo del servidor de correo corporativo sobre **Debian Server**.

---

## Contenido de la Carpeta

| Archivo | Descripción |
| :--- | :--- |
| **`main.cf`** | Configuración principal de Postfix: dominio corporativo, redes autorizadas, Maildir y TLS. |
| **`master.cf`** | Definición de servicios maestros de Postfix (SMTP en puerto 25, Submission 587 y SMTPS 465). |
| **`dovecot.conf`** | Configuración raíz de Dovecot: protocolos habilitados (IMAP/POP3) y modularidad. |
| **`10-mail.conf`** | Configuración de almacenamiento tipo Maildir (`~/Maildir`) en el directorio de cada usuario. |
| **`10-auth.conf`** | Configuración de autenticación Dovecot (mecanismos PLAIN/LOGIN y backend de sistema Linux). |
| **`10-ssl.conf`** | Configuración de cifrado SSL/TLS para IMAP seguro con certificados corporativos. |
| **`10-master.conf`** | Configuración del socket de autenticación UNIX para la integración entre Postfix y Dovecot. |
| **`crear_buzones.sh`** | Script bash para la creación automática de cuentas de prueba (`usuario1`, `usuario2`) y carpetas Maildir. |
| **`MANUAL_PRUEBAS.md`** | Manual de pruebas con comandos exactos: DNS MX/SPF, trazabilidad en vivo con `tail -f` y lectura de buzón. |
| **`GUION_VIDEO.md`** | Guion narrativo detallado para la grabación del video en YouTube con cámara web obligatoria. |

