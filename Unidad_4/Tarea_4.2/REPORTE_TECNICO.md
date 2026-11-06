# Reporte Técnico: Tarea 4.2 – Stack de Servicios Web (HTTPS) y Transferencia de Archivos (vsftpd/SFTP)

**Materia:** Administración de Servidores  
**Unidad:** 4 – Servidores DNS, Web, FTP, DHCP, Correos  
**Alumno:** [Nombre del Alumno]  
**Docente:** [Nombre del Docente]  
**Fecha:** Noviembre 2026  
**Servidor:** Debian GNU/Linux 12 (Apache2 + PHP 8.2 + MariaDB + vsftpd)  
**Cliente:** Debian GNU/Linux 12 (XFCE Desktop con Firefox y FileZilla) / Windows  
**Hipervisor:** VMware Workstation (LAN Segment: `Lab-Red`)  

---

## 1. Introducción y Objetivos

El despliegue de aplicaciones modernas en entornos empresariales exige el cumplimiento estricto de dos pilares de seguridad:
1. **Confidencialidad e Integridad en Tránsito (HTTPS):** La capa de transporte de la web debe estar protegida mediante cifrado SSL/TLS robusto, impidiendo ataques de interceptación (*Man-in-the-Middle*) o alteración de datos.
2. **Aislamiento y Principio de Menor Privilegio en Transferencia de Archivos (FTP Seguro):** El almacenamiento remoto de archivos debe impedir accesos anónimos no autorizados y confinar a cada usuario en su directorio asignado (**Chroot Jail**), previniendo que usuarios maliciosos exploren la jerarquía del sistema operativo o descarguen archivos confidenciales como `/etc/passwd` o `/etc/shadow`.

El objetivo de este reporte es documentar el despliegue del stack LAMP (Linux, Apache, MariaDB, PHP) bajo HTTPS con Virtual Hosts y la securización de un servidor **vsftpd** con enjaulamiento chroot.

---

## 2. Topología de Servicios

```mermaid
flowchart LR
    subgraph Cliente["Estación de Trabajo Cliente: Debian XFCE"]
        Browser["Navegador Web Firefox"]
        FTPClient["FileZilla / Terminal FTP"]
    end

    subgraph DebianServer["Debian GNU/Linux Server (192.168.50.10)"]
        subgraph WebStack["Stack Web Seguro"]
            Apache80["Apache :80 (HTTP)\n[Redirección Permanente 301]"]
            Apache443["Apache :443 (HTTPS / SSL/TLS)\nVirtualHost: empresa.local\nCertificado SAN con TLS 1.2 / 1.3"]
            PHP["Intérprete PHP 8.2\n(Páginas dinámicas)"]
            MariaDB[("Base de Datos MariaDB\nempresa_db")]
        end

        subgraph FTPStack["Servidor de Archivos Seguro"]
            VSFTPD["vsftpd :21\nanonymous_enable = NO\nchroot_local_user = YES"]
            Jail["Directorio Enjaulado\n/home/ftpuser/ftp/archivos/\n(Incapaz de acceder a /etc o /var)"]
        end
    end

    Browser -->|HTTP Solicitud :80| Apache80
    Apache80 -.->|301 Redirect HTTPS| Apache443
    Browser <==|Canal Cifrado SSL/TLS :443| Apache443
    Apache443 --> PHP --> MariaDB

    FTPClient <==|Conexión Autenticada :21| VSFTPD
    VSFTPD --> Jail
```

### Matriz de Servicios y Puertos

| Servicio | Demonio / Software | Puerto(s) | Protocolo / Modo | Seguridad Implementada |
| :--- | :--- | :--- | :--- | :--- |
| **Redirección Web** | Apache2 | TCP 80 | HTTP | Redirección obligatoria 301 a HTTPS. |
| **Portal Web Seguro** | Apache2 + PHP | TCP 443 | HTTPS (TLS 1.2/1.3) | Certificado SSL con SAN, cifrados robustos ECDHE. |
| **Base de Datos** | MariaDB Server | TCP 3306 | Localhost (127.0.0.1) | Usuario dedicado `empresa_user` con privilegios mínimos. |
| **Control FTP** | vsftpd | TCP 21 | FTP / FTPS | Bloqueo anónimo, autenticación local obligatoria. |
| **Datos FTP Pasivo** | vsftpd | TCP 40000-40100 | Rango Pasivo | Puertos restringidos para firewall/NAT. |
| **Aislamiento** | Linux Filesystem | N/A | Chroot Jail | Permisos `nobody:nogroup` no escribible en raíz de jaula. |

---

## 3. Resumen de Archivos de Configuración Comentados

### 3.1 Virtual Host SSL (`/etc/apache2/sites-available/empresa.local-ssl.conf`)
* **DocumentRoot:** `/var/www/empresa.local/public_html`
* **Directivas SSL:**
  ```text
  SSLEngine on
  SSLCertificateFile /etc/ssl/certs/empresa.local.crt
  SSLCertificateKeyFile /etc/ssl/private/empresa.local.key
  SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
  SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:...
  ```
* **Virtual Host Redirección:** Puerto 80 configurado con `RewriteEngine On` y `RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`.

### 3.2 Servidor vsftpd (`/etc/vsftpd.conf`)
* **Directivas de seguridad aplicadas:**
  ```text
  anonymous_enable=NO
  local_enable=YES
  write_enable=YES
  local_umask=022
  chroot_local_user=YES
  allow_writeable_chroot=YES
  pasv_enable=YES
  pasv_min_port=40000
  pasv_max_port=40100
  ```

---

## 4. Guía de Evidencias Fotográficas Requeridas para el PDF

> **Instrucciones para el Alumno:**  
> A continuación se detallan las **6 capturas exactas** que debes incluir en tu memoria técnica de la Tarea 4.2:

---

### 📸 Evidencia 1: Inspección del Virtual Host y Sintaxis de Apache
* **Comandos en Debian Server:**
  ```bash
  sudo apache2ctl -S
  sudo apache2ctl configtest
  ```
* **Qué debe verse:** El listado de los Virtual Hosts cargados en el puerto 80 y 443 asociados al dominio `empresa.local` y el mensaje final `Syntax OK`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 1: Verificación de sintaxis y Virtual Hosts de Apache2]`  
  > *Figura 2.1: Comprobación del motor de Virtual Hosts y ausencia de errores de configuración en el servidor web.*

---

### 📸 Evidencia 2: Navegación Segura HTTPS y Portal Dinámico PHP/MariaDB
* **Acción en el Cliente (Debian XFCE / Windows):** Abrir Firefox o Chrome e ingresar a `https://www.empresa.local`.
* **Qué debe verse:** La página web corporativa mostrando el banner azul de **🔒 HTTPS (Cifrado Activo)**, la versión de PHP detectada y el recuadro verde con la leyenda **Conectado exitosamente a MariaDB**.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 2: Navegación web en https://www.empresa.local]`  
  > *Figura 2.2: Carga exitosa del portal web interactivo bajo canal cifrado HTTPS y conexión activa a MariaDB.*

---

### 📸 Evidencia 3: Inspección Visual del Certificado SSL/TLS (Nombres SAN)
* **Acción en el Cliente:** Hacer clic en el candado del navegador > **Ver Certificado / Detalles**.
* **Qué debe verse:** La ventana modal del visor de certificados mostrando:
  - Sujeto: `CN = empresa.local`, Organización: `Empresa Corporativa S.A.`
  - Algoritmo de firma y validez.
  - Sección **Subject Alternative Name (SAN)** con los nombres `DNS:empresa.local`, `DNS:www.empresa.local` e `IP Address:192.168.50.10`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 3: Detalles del Certificado Digital SSL/TLS con SAN]`  
  > *Figura 2.3: Validación criptográfica de la entidad emisora y los nombres alternativos del certificado.*

---

### 📸 Evidencia 4: Redirección Automática de HTTP a HTTPS y Consulta JSON
* **Acción en el Cliente:**
  - Ingresar en el navegador a `http://empresa.local` (puerto 80) y verificar la redirección instantánea a `https://`.
  - Navegar a `https://www.empresa.local/db_test.php` mostrando la salida de la base de datos en formato JSON.
* **Qué debe verse:** La respuesta JSON formateada con los empleados consultados de la tabla `empresa_db.empleados`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 4: Redirección permanente 301 y API JSON de MariaDB]`  
  > *Figura 2.4: Comprobación de redirección segura forzosa e interoperabilidad PHP-MariaDB.*

---

### 📸 Evidencia 5: Bloqueo de Acceso Anónimo en vsftpd
* **Comando en el Cliente (Terminal Linux o Consola Windows):**
  ```bash
  ftp 192.168.50.10
  ```
  Ingresar usuario: `anonymous` (o dejar contraseña vacía).
* **Qué debe verse:** El mensaje de error del servidor: `530 Permission denied` o `Login incorrect`, finalizando la sesión sin permitir el acceso al sistema de archivos.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 5: Rechazo de acceso a usuarios anónimos en vsftpd]`  
  > *Figura 2.5: Implementación de directiva de seguridad impidiendo conexiones no autenticadas.*

---

### 📸 Evidencia 6: Demostración de Enjaulamiento (Chroot Jail) y Transferencia
* **Acción en el Cliente (Terminal FTP o FileZilla):**
  - Iniciar sesión con `ftpuser` y contraseña `Alumno123!`.
  - Ejecutar `pwd` (muestra `/`).
  - Ejecutar `cd ..`, `cd /etc` o `cd /var` (muestra `550 Failed to change directory`).
  - Ingresar a `cd archivos`, listar con `ls` o `dir`, descargar archivo de prueba con `get` y subir uno nuevo con `put`.
* **Qué debe verse:** El confinamiento dentro de la jaula chroot donde la raíz es virtualizada, la denegación de escape y la subida/descarga exitosa con código `226 Transfer complete`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 6: Verificación de Chroot Jail y transferencia de archivos]`  
  > *Figura 2.6: Evidencia del aislamiento del usuario impidiendo explorar el sistema operativo host.*

---

## 5. Conclusiones Técnicas

1. **Seguridad Web Criptográfica:** El empleo de TLS moderno junto con la redirección permanente elimina el riesgo de envío de credenciales o sesiones en texto plano, mientras que los certificados con SAN garantizan compatibilidad completa con navegadores modernos.
2. **Defensa en Profundidad con Chroot:** El enjaulamiento de usuarios en `vsftpd` representa una técnica de contención de primer nivel: incluso si las credenciales de un usuario FTP fueran comprometidas, el atacante queda atrapado en un sandbox sin posibilidad de leer archivos de configuración, llaves privadas SSL o binarios del sistema.
3. **Integración Completa del Stack:** Se validó exitosamente la interacción entre el servidor web, el motor de base de datos relacional y el subsistema de almacenamiento de archivos.

---

## 6. Enlaces de Entrega

* **Repositorio Público de GitHub:** `https://github.com/[Tu_Usuario]/[Tu_Repositorio]/tree/main/Unidad_4/Tarea_4.2`
* **Video Demostrativo en YouTube:** `https://youtu.be/[ID_DE_TU_VIDEO]`
