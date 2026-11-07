# Guion Maestro de Demostración Técnica: Tarea 4.2 (Stack Web HTTPS Seguro y Servidor FTP Enjaulado)

> **Perfil del Expositor:** Ingeniero en Sistemas y Especialista en Seguridad e Infraestructura Web Linux.  
> **Tono y Estilo:** Formal, técnico, académico, riguroso y didáctico. Sin frases informales ni modismos de redes sociales.  
> **Formato de Grabación:** OBS Studio con captura de pantalla limpia y **cámara web activa en la esquina inferior derecha** durante toda la demostración.  
> **Disposición del Entorno en Pantalla:** Dos máquinas virtuales en VMware Workstation:
> - **Lado Izquierdo:** `Debian Server` (Consola de administración). IP: `192.168.50.10`.
> - **Lado Derecho:** `Debian XFCE Cliente` (Estación de trabajo con Mozilla Firefox y FileZilla). IP: `192.168.50.25`.
> **Segmento de Red:** VMware LAN Segment privado `Lab-Red` (`192.168.50.0/24`).  
> **Duración estimada:** 12 a 15 minutos.

---

## ESTRUCTURA DE LA DEMOSTRACIÓN

```
├── A. INTRODUCCIÓN Y AMENAZAS EN COMUNICACIONES WEB (00:00 - 02:30)
│   ├── Identificación de la práctica y vulnerabilidad del texto claro
│   ├── Objetivos: Stack LAMP + Cifrado TLS con SAN + Chroot Jail
│   └── Topología de red y entorno de pruebas en VMware
│
├── B. FUNDAMENTOS CRIPTOGRÁFICOS Y DE CONTENCIÓN (02:30 - 04:30)
│   ├── Mecanismo del protocolo TLS y la extensión Subject Alternative Name (SAN)
│   ├── Redirección obligatoria 301 (Moved Permanently) en capa de aplicación
│   ├── Interacción del Stack LAMP (Apache, PHP con PDO y MariaDB)
│   └── Principio de contención de procesos mediante Chroot Jail (Change Root)
│
├── C. DESARROLLO, ANÁLISIS LÍNEA POR LÍNEA Y PRUEBAS EN VIVO (04:30 - 13:30)
│   ├── Paso 1: Instalación de paquetes del stack LAMP y VSFTPD
│   ├── Paso 2: Análisis del script OpenSSL para certificados SAN (generar_certificados.sh)
│   ├── Paso 3: Desglose línea por línea de VirtualHosts en Apache (Puertos 80 y 443)
│   ├── Paso 4: Base de datos relacional MariaDB y scripts dinámicos PHP (JSON API)
│   ├── Paso 5: Desglose línea por línea de la configuración de VSFTPD (/etc/vsftpd.conf)
│   ├── Paso 6: Verificación de demonios y prueba de sintaxis (apache2ctl configtest)
│   └── Paso 7: Comprobación y auditoría en vivo desde la estación cliente
│
└── D. CONCLUSIONES TÉCNICAS Y CIERRE FORMAL (13:30 - 14:30)
    ├── Validación de cumplimiento de controles de seguridad
    └── Disponibilidad de código en el repositorio institucional y cierre
```

---

## A. INTRODUCCIÓN Y AMENAZAS EN COMUNICACIONES WEB

### [00:00 - 02:30] Escena 1: Presentación, Justificación de Seguridad y Arquitectura

* **En pantalla:** Escritorio de VMware mostrando las dos máquinas virtuales: a la izquierda la consola de `Debian Server` y a la derecha el escritorio de `Debian XFCE`. Cámara web visible en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Estimado profesor y compañeros, bienvenidos a la presentación técnica de la Tarea 4.2 de la materia Administración de Servidores, correspondiente a la Unidad 4. Mi nombre es [Tu Nombre Completo].*
  > 
  > *En esta práctica abordaremos la implementación y securización de dos de los servicios de aplicación más críticos en entornos corporativos: la publicación web segura mediante un **Stack LAMP bajo protocolo HTTPS con certificados TLS** y la transferencia controlada de archivos mediante **VSFTPD protegido con confinamiento Chroot Jail**.*
  > 
  > *El problema de seguridad fundamental que resolvemos hoy radica en la vulnerabilidad de las comunicaciones en texto plano. Cuando un usuario interactúa mediante el protocolo HTTP estándar o transmite archivos mediante FTP tradicional, cualquier paquete que viaje por el canal de red puede ser interceptado, analizado y reconstruido por un atacante mediante técnicas de captura de tráfico como 'Man-In-The-Middle' (MITM).*
  > 
  > *Para blindar esta infraestructura, implementaremos tres defensas de ingeniería:*
  > *1. **Cifrado de Capa de Transporte (TLS 1.2 y 1.3):** Generaremos certificados digitales que incorporan extensiones **SAN (Subject Alternative Name)**, garantizando la autenticidad e integridad del canal tanto por nombre de dominio como por dirección IP.*
  > *2. **Redirección Permanente 301 en Apache:** Aseguraremos que ninguna petición en el puerto 80 quede expuesta, forzando la elevación automática hacia el puerto seguro 443.*
  > *3. **Confinamiento de Procesos Chroot Jail en VSFTPD:** Bloquearemos cualquier intento de acceso anónimo y enjaularemos a los usuarios autenticados dentro de su propio árbol de directorios, impidiendo físicamente cualquier navegación transversal hacia la raíz del sistema operativo.*
  > 
  > *La topología opera dentro del LAN Segment `Lab-Red` (`192.168.50.0/24`), con el servidor en `192.168.50.10` y la estación cliente en `192.168.50.25`. Pasemos a revisar la implementación técnica."*

---

## B. FUNDAMENTOS CRIPTOGRÁFICOS Y DE CONTENCIÓN

### [02:30 - 04:30] Escena 2: Criptografía TLS, Nombres SAN y Mecánica Chroot

* **En pantalla:** Diagrama de topología de la Tarea 4.2 o terminal de Debian Server.
* **Lo que dices verbalmente:**
  > *"Revisemos los principios técnicos que sustentan la configuración:*
  > 
  > *Primero: **Criptografía TLS y la extensión SAN (Subject Alternative Name)**.  
  > En los estándares criptográficos modernos, los navegadores y clientes rechazan los certificados que únicamente definen el campo tradicional `Common Name` (CN). La extensión SAN (definida en el RFC 5280) permite incorporar una lista de identidades canónicas asociadas a una misma clave pública: en nuestro caso, vincularemos `empresa.local`, el subdominio `www.empresa.local` y la dirección IPv4 `192.168.50.10`.*
  > 
  > *Segundo: **Redirección de Capa de Aplicación mediante Código HTTP 301**.  
  > El módulo `mod_rewrite` de Apache intercepta el tráfico entrante en el puerto 80 y emite un encabezado HTTP con el código de estado `301 Moved Permanently`. A diferencia de un código temporal 302, el código 301 instruye al cliente y a los motores de búsqueda a actualizar en su caché la referencia definitiva hacia el recurso seguro bajo `https://`.*
  > 
  > *Tercero: **La llamada al sistema `chroot` en VSFTPD**.  
  > En el núcleo Linux, la llamada al sistema `chroot()` redefine cuál es el nodo raíz (`/`) para el árbol de procesos del usuario conectado. Cuando el demonio `vsftpd` confina a un usuario dentro de `/home/ftpuser`, el sistema operativo enmascara las estructuras superiores. Aunque el usuario ejecute comandos de retroceso como `cd ..` o intente rutas directas como `/etc/shadow`, el kernel bloquea la operación puesto que, para ese proceso, no existe ningún directorio superior a su propia jaula.*
  > 
  > *Pasemos a la consola para ejecutar la configuración e inspeccionar cada directiva."*

---

## C. DESARROLLO, ANÁLISIS LÍNEA POR LÍNEA Y PRUEBAS EN VIVO

### [04:30 - 05:45] Paso 1: Instalación de Paquetes del Stack LAMP y VSFTPD

* **En pantalla:** Terminal de `Debian Server`.
* **Lo que dices verbalmente:**
  > *"En la terminal de `Debian Server` instalamos los paquetes que conforman la pila completa de servicios web y de archivos ejecutando:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo apt update && sudo apt install -y apache2 mariadb-server php libapache2-mod-php php-mysql vsftpd openssl
  ```
* **Explicación técnica del comando:**
  > *- `apache2`: Servidor HTTP/HTTPS modular.*
  > *- `mariadb-server`: Sistema de gestión de bases de datos relacionales SQL.*
  > *- `php` y `libapache2-mod-php`: Intérprete del lenguaje PHP y el módulo que permite a Apache procesar scripts dinámicos en memoria.*
  > *- `php-mysql`: Controlador de extensiones PDO para establecer conexiones seguras entre PHP y MariaDB.*
  > *- `vsftpd`: Demonio FTP de alta seguridad diseñado para evitar desbordamientos de búfer.*
  > *- `openssl`: Biblioteca y conjunto de herramientas para la generación y gestión de claves y certificados X.509.*
  > 
  > *Una vez completada la instalación, todos los módulos base se encuentran registrados en el sistema operativo."*

---

### [05:45 - 07:30] Paso 2: Generación del Certificado SSL/TLS con SAN (OpenSSL)

* **En pantalla:** Terminal de Debian Server mostrando el script `generar_certificados.sh` con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Para automatizar la emisión del certificado corporativo bajo estándares modernos, creamos el script `generar_certificados.sh`. Inspeccionemos su lógica línea por línea con `cat -n`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.2/generar_certificados.sh
  ```
* **Explicación técnica línea por línea:**
  > *- **Líneas 5 y 6:** Definen las rutas canónicas del sistema de archivos donde se alojará el par de llaves: `/etc/ssl/certs/empresa.local.crt` para el certificado público y `/etc/ssl/private/empresa.local.key` para la clave privada.*
  > *- **Líneas 10 a 24:** Construyen un archivo de configuración temporal para OpenSSL que inyecta la sección de nombres alternativos SAN:*
  >   ```text
  >   DNS.1 = empresa.local
  >   DNS.2 = www.empresa.local
  >   IP.1 = 192.168.50.10
  >   ```
  > *- **Líneas 28 a 33:** Invocan el binario de OpenSSL con sus parámetros formales:*
  >   - `req -x509`: Solicita la generación de un certificado autofirmado bajo el estándar X.509.*
  >   - `-nodes`: Significa 'no DES'; omite la protección por contraseña en la clave privada para permitir que Apache se reinicie automáticamente en segundo plano sin intervención humana.*
  >   - `-days 365`: Establece el período de validez en un año natural.*
  >   - `-newkey rsa:2048`: Genera un nuevo par de claves asimétricas RSA de 2048 bits.*
  > *- **Líneas 36 y 37:** Aplican control de acceso estricto mediante `chmod 600` sobre la clave privada, garantizando que solo el usuario root pueda leer el material criptográfico confidencial.*
  > 
  > *Ejecutamos el script de generación:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  chmod +x generar_certificados.sh && sudo ./generar_certificados.sh
  ```
* **Resultado:**
  > *- El script confirma la creación de los archivos y la aplicación de permisos en `/etc/ssl/`."*

---

### [07:30 - 09:30] Paso 3: Análisis Detallado Línea por Línea de VirtualHosts de Apache

* **En pantalla:** Terminal de Debian Server mostrando los archivos de configuración de Apache con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Inspeccionemos la configuración de los Virtual Hosts en Apache. Para el puerto 80 visualizamos `empresa.local.conf`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n /etc/apache2/sites-available/empresa.local.conf
  ```
* **Explicación técnica línea por línea:**
  > *- **Línea 1 (`<VirtualHost *:80>`):** Declara el socket de escucha para cualquier interfaz en el puerto TCP 80.*
  > *- **Líneas 2 y 3 (`ServerName` y `ServerAlias`):** Asignan el dominio principal `empresa.local` y el subdominio `www.empresa.local`.*
  > *- **Línea 5 (`RewriteEngine On`):** Habilita el motor de reescritura de URLs de Apache.*
  > *- **Línea 6 (`RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`):** Intercepta la totalidad de la solicitud mediante la expresión regular `^(.*)$`, reescribe el esquema a `https://` y emite la cabecera `301 Moved Permanently`. La bandera `L` detiene el procesamiento de reglas posteriores.*
  > 
  > *Ahora inspeccionemos el Virtual Host seguro en el puerto 443 con `cat -n`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n /etc/apache2/sites-available/empresa.local-ssl.conf
  ```
* **Explicación técnica línea por línea:**
  > *- **Línea 1 (`<VirtualHost *:443>`):** Habilita la atención de tráfico en el puerto seguro HTTPS.*
  > *- **Línea 4 (`DocumentRoot /var/www/html/empresa.local`):** Define la raíz en disco para los recursos del sitio web.*
  > *- **Línea 6 (`SSLEngine on`):** Inicializa el procesador criptográfico SSL/TLS.*
  > *- **Líneas 7 y 8 (`SSLCertificateFile` y `SSLCertificateKeyFile`):** Apuntan a las rutas del certificado X.509 y la clave privada generados previamente.*
  > *- **Línea 10 (`SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1`):** Política estricta de endurecimiento criptográfico; desactiva protocolos obsoletos y vulnerables a ataques conocidos (como POODLE o BEAST), permitiendo exclusivamente **TLS 1.2 y TLS 1.3**.*
  > 
  > *Habilitamos los módulos y comprobamos la sintaxis del servidor web:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  sudo a2enmod ssl rewrite
  sudo a2ensite empresa.local.conf empresa.local-ssl.conf
  sudo apache2ctl configtest
  ```
* **Resultado:**
  > *- La herramienta `apache2ctl configtest` nos devuelve el mensaje formal **Syntax OK**, certificando la ausencia de errores estructurales."*

---

### [09:30 - 11:00] Paso 4: Base de Datos MariaDB y Endpoint Dinámico en PHP

* **En pantalla:** Terminal de Debian Server mostrando MariaDB y la estructura web.
* **Lo que dices verbalmente:**
  > *"Configuramos la persistencia de datos en MariaDB creando la base de datos `empresa_db`, una tabla relacional `empleados` con registros de prueba y un usuario restringido `webuser` con contraseña `Empresa2026!` que únicamente posee permisos de consulta sobre dicha tabla.*
  > 
  > *En `/var/www/html/empresa.local/` implementamos dos scripts PHP:*
  > *1. `index.php`: Interfaz gráfica interactiva que valida mediante bloques de control `try-catch` y la biblioteca PDO la conexión contra MariaDB, mostrando en pantalla el estado de seguridad HTTPS, la versión del motor PHP y los parámetros de red.*
  > *2. `db_test.php`: Script que realiza una consulta SQL `SELECT` y transforma el resultado en una estructura **JSON** estándar, emitiendo las cabeceras `Content-Type: application/json`.*
  > 
  > *Reiniciamos ambos demonios y validamos su estado operativo:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart apache2 mariadb
  sudo systemctl status apache2 mariadb --no-pager
  ```
* **Resultado:**
  > *- Ambos servicios reportan el estado en color verde: **active (running)**."*

---

### [11:00 - 12:15] Paso 5: Desglose Línea por Línea de VSFTPD (`vsftpd.conf`)

* **En pantalla:** Terminal de Debian Server mostrando `/etc/vsftpd.conf` con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Procedemos a auditar la configuración de seguridad del demonio FTP inspeccionando `/etc/vsftpd.conf` con `cat -n`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n /etc/vsftpd.conf
  ```
* **Explicación técnica detallada línea por línea:**
  > *- **Línea 3 (`anonymous_enable=NO`):** Clausura el acceso anónimo; ninguna sesión es admitida sin credenciales registradas.*
  > *- **Línea 4 (`local_enable=YES`):** Permite a los usuarios locales de Linux autenticarse a través del subsistema PAM.*
  > *- **Línea 5 (`write_enable=YES`):** Otorga permisos para operaciones de subida (`STOR`), borrado y modificación de archivos.*
  > *- **Línea 6 (`local_umask=022`):** Asigna la máscara de creación estándar; los archivos se crean con permisos 644 y las carpetas con 755.*
  > *- **Línea 8 (`chroot_local_user=YES`):** Directiva clave de confinamiento. Le ordena al proceso activar la llamada `chroot` sobre el directorio personal del usuario conectado.*
  > *- **Línea 9 (`allow_writeable_chroot=YES`):** Resuelve la restricción de seguridad de vsftpd, permitiendo que la raíz enjaulada posea permisos de escritura sin anular el servicio.*
  > *- **Líneas 11 a 13 (`pasv_enable=YES`, `pasv_min_port=40000`, `pasv_max_port=40100`):** Establecen el rango de puertos efímeros para transferencias de datos en modo pasivo, permitiendo un control granular en cortafuegos.*
  > *- **Línea 15 (`pam_service_name=vsftpd`):** Vincula el servicio al archivo de autenticación PAM `/etc/pam.d/vsftpd`.*
  > 
  > *Para la prueba creamos el usuario `ftpuser` con la contraseña estandarizada `Alumno123`, asignando la propiedad de su carpeta de trabajo mediante `chown -R ftpuser:ftpuser /home/ftpuser/archivos`.*
  > 
  > *Reiniciamos el servicio y verificamos su estado:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart vsftpd
  sudo systemctl status vsftpd --no-pager
  ```
* **Resultado:**
  > *- El demonio `vsftpd` se encuentra activo y escuchando peticiones en el puerto TCP 21."*

---

### [12:15 - 14:15] Paso 6: Verificación y Auditoría de Servicios desde el Cliente

* **En pantalla:** Enfoque en la máquina derecha: `Debian XFCE Cliente`.
* **Lo que dices verbalmente:**
  > *"Nos trasladamos a la estación de trabajo cliente con Debian XFCE para auditar la operatividad de ambos servicios.*
  > 
  > *Auditoría Web 1: Redirección permanente HTTP a HTTPS.*
  > *Abrimos Mozilla Firefox e ingresamos explícitamente a la dirección insecure: `http://www.empresa.local`.*
  > *Observamos cómo la barra de direcciones eleva instantáneamente el protocolo a `https://www.empresa.local`, certificando el funcionamiento de la regla 301 de Apache.*
  > 
  > *Auditoría Web 2: Inspección del Certificado SSL con SAN.*
  > *Hacemos clic en el icono del candado y seleccionamos 'Ver Certificado'. En los detalles podemos constatar:*
  > *- Sujeto: `CN = empresa.local` y Organización `Empresa Corporativa S.A.`.*
  > *- Extensión SAN (Subject Alternative Name): Valida formalmente a `empresa.local`, `www.empresa.local` y la IP `192.168.50.10`.*
  > 
  > *Auditoría Web 3: Conectividad LAMP y API JSON.*
  > *En la interfaz web se despliega el banner de conexión segura HTTPS, la versión de PHP y el recuadro verde que confirma: **Conectado exitosamente a MariaDB**.*
  > *Al abrir la ruta `https://www.empresa.local/db_test.php`, observamos la serialización en formato JSON de los registros extraídos desde la tabla relacional.*
  > 
  > *Auditoría FTP 1: Bloqueo de usuarios anónimos.*
  > *En la terminal del cliente ejecutamos el cliente FTP intentando autenticar con `anonymous`:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  ftp 192.168.50.10
  ```
  *(Al solicitar usuario escribimos `anonymous`)*.
* **Resultado:**
  > *- El servidor rechaza la conexión con el código `530 Login incorrect`, confirmando el endurecimiento de acceso.*
  > 
  > *Auditoría FTP 2: Autenticación de usuario y demostración de Chroot Jail.*
  > *Reconectamos ingresando con `ftpuser` y la contraseña `Alumno123`. El servidor nos devuelve el código `230 Login successful`.*
  > *Comprobemos el aislamiento ejecutando los comandos de inspección:*
* **Comandos en la sesión FTP interactiva:**
  ```text
  ftp> pwd
  ftp> cd ..
  ftp> pwd
  ftp> cd /etc
  ```
* **Interpretación técnica:**
  > *- Al ejecutar `pwd`, el sistema nos ubica en la raíz virtual `/`.*
  > *- Al ejecutar `cd ..` y verificar nuevamente con `pwd`, constatamos que seguimos en `/`.*
  > *- Al forzar la navegación hacia directorios confidenciales del sistema mediante `cd /etc`, el servidor deniega la operación devolviendo el código `550 Failed to change directory`.*
  > *Esto certifica que el usuario carece de visibilidad sobre cualquier archivo fuera de su jaula.*
  > 
  > *Auditoría FTP 3: Transferencia de datos.*
  > *Ingresamos a `cd archivos`, ejecutamos operaciones de subida con `put` y descarga con `get`. Asimismo, al abrir el cliente gráfico **FileZilla** y autenticar hacia `192.168.50.10`, verificamos la transferencia exitosa de archivos dentro del entorno restringido."*

---

## D. CONCLUSIONES TÉCNICAS Y CIERRE FORMAL

### [14:15 - 15:15] Escena 7: Síntesis de Resultados y Cierre

* **En pantalla:** Cámara web en primer plano con el entorno virtualizado de fondo.
* **Lo que dices verbalmente:**
  > *"Con estas comprobaciones prácticas damos por concluida formalmente la evaluación técnica de la Tarea 4.2.*
  > 
  > *Sintetizamos los principios de ingeniería aplicados:*
  > *1. **Protección Criptográfica Integral:** Implementamos el stack **LAMP** asegurando el canal con **TLS 1.2 y 1.3**, incorporando certificados digitales con soporte para **Subject Alternative Names (SAN)** y forzando la elevación segura de conexiones mediante redirecciones **301**.*
  > *2. **Integración y Exposición de Datos:** Consolidamos la persistencia en base de datos mediante **MariaDB y PHP con PDO**, validando la generación de respuestas de datos estructurados en formato JSON.*
  > *3. **Principio de Mínimo Privilegio y Contención:** Securizamos **VSFTPD** mediante el bloqueo absoluto de sesiones anónimas y la aplicación de jaulas **Chroot**, neutralizando posibles vectores de escalamiento de privilegios o fuga de información.*
  > 
  > *Todos los archivos de configuración, scripts de automatización OpenSSL y la memoria técnica con capturas de evidencia se encuentran a su disposición en el repositorio institucional de GitHub.*
  > 
  > *Agradezco su atención a esta presentación técnica."*
