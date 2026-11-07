# Guion Maestro del Video Tutorial: Tarea 4.2 (Stack Web HTTPS Seguro y Servidor FTP Enjaulado)

> **Perfil del Presentador:** Ingeniero Senior en Sistemas y Especialista en Ciberseguridad e Infraestructura Web Linux.  
> **Formato de Grabación:** OBS Studio con captura de pantalla y **cámara web activa en la esquina inferior derecha** en todo momento.  
> **Disposición de Pantalla:** Dos máquinas virtuales en VMware Workstation:
> - **Lado Izquierdo:** `Debian Server` (Consola de administración). IP: `192.168.50.10`.
> - **Lado Derecho:** `Debian XFCE Cliente` (Escritorio con Mozilla Firefox y FileZilla). IP: `192.168.50.25`.
> **Segmento de Red:** VMware LAN Segment aislado `Lab-Red` (`192.168.50.0/24`).  
> **Duración aproximada:** 12 a 16 minutos (Tutorial didáctico de nivel avanzado).

---

## ESTRUCTURA DEL TUTORIAL

```
├── A. INTRODUCCIÓN Y OBJETIVOS (00:00 - 02:00)
│   ├── Presentación y riesgos de la transferencia en texto claro
│   ├── Qué construiremos: Stack LAMP + Cifrado TLS + Chroot Jail
│   └── Entorno de laboratorio y requisitos previos
│
├── B. FUNDAMENTOS TÉCNICOS ESENCIALES (02:00 - 04:30)
│   ├── Cómo funciona el apretón de manos TLS (Handshake) y certificados SAN
│   ├── Redirección HTTP a HTTPS mediante códigos de estado 301
│   ├── Arquitectura del Stack LAMP (Apache, MariaDB y PHP)
│   └── Principio de contención Chroot Jail (Change Root) en VSFTPD
│
├── C. IMPLEMENTACIÓN PRÁCTICA PASO A PASO (04:30 - 13:30)
│   ├── Paso 1: Instalación de paquetes del Stack Web y FTP
│   ├── Paso 2: Generación del Certificado SSL/TLS con SAN mediante OpenSSL
│   ├── Paso 3: Configuración de VirtualHosts en Apache (Puertos 80 y 443)
│   ├── Paso 4: Creación de Base de Datos MariaDB y Scripts PHP Dinámicos
│   ├── Paso 5: Configuración y Enjaulamiento de VSFTPD
│   ├── Paso 6: Verificación de Demonios y Prueba de Sintaxis
│   └── Paso 7: Demostración y Pruebas en Vivo desde el Cliente Debian XFCE
│
└── D. CONCLUSIÓN Y MEJORES PRÁCTICAS (13:30 - 15:00)
    ├── Resumen de la defensa en profundidad implementada
    └── Enlaces al repositorio GitHub y despedida
```

---

## A. INTRODUCCIÓN Y OBJETIVOS

### [00:00 - 02:00] Escena 1: Presentación, Planteamiento del Problema y Resultado Final

* **En pantalla:** Escritorio de VMware en pantalla completa. A la izquierda la terminal del servidor `Debian Server`; a la derecha el escritorio de `Debian XFCE` con el navegador Firefox abierto. Cámara web visible en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola a todos y bienvenidos a este nuevo tutorial práctico sobre Administración y Seguridad de Servidores Linux. Mi nombre es [Tu Nombre Completo]. En esta sesión vamos a construir y securizar dos servicios web y de transferencia de archivos que son el corazón operativo de cualquier empresa: un **Stack LAMP completo bajo protocolo seguro HTTPS** y un **Servidor de Archivos VSFTPD blindado mediante Chroot Jail**.*
  > 
  > *Pensemos en el riesgo que enfrentamos hoy en día: si un usuario navega por un sitio web mediante HTTP tradicional o sube archivos mediante FTP estándar, todos sus datos, contraseñas y sesiones viajan por el cable de red en texto completamente legible. Cualquier atacante con herramientas básicas como Wireshark podría interceptar sus credenciales al instante.*
  > 
  > *Para erradicar esta vulnerabilidad, hoy implementaremos tres capas de protección profesional:*
  > *1. Un servidor web **Apache2** que forzará la redirección permanente mediante código **301** de cualquier intento de conexión insegura hacia el puerto seguro 443.*
  > *2. Generaremos nuestros propios **Certificados Digitales SSL/TLS con soporte para Nombres Alternativos del Sujeto (SAN)**, el estándar exigido por los navegadores modernos para evitar advertencias de identidad.*
  > *3. Conectaremos Apache con el motor de base de datos **MariaDB** a través del lenguaje dinámico **PHP**, demostrando la persistencia de datos y la exposición de una API JSON corporativa.*
  > *4. Y en el servidor de archivos **VSFTPD**, bloquearemos totalmente el acceso anónimo y confinaremos a los usuarios autenticados dentro de una 'prisión digital' conocida como **Chroot Jail**, impidiéndoles salir de su carpeta personal para que jamás puedan explorar el sistema de archivos del servidor.*
  > 
  > *Todo el procedimiento lo realizaremos dentro de nuestro entorno privado en VMware sobre el segmento `Lab-Red`, comunicando nuestro servidor Debian Server con la IP `192.168.50.10` y nuestra estación cliente Debian XFCE con la IP `192.168.50.25`. ¡Acompáñenme a la consola!"*

---

## B. FUNDAMENTOS TÉCNICOS ESENCIALES

### [02:00 - 04:30] Escena 2: Criptografía TLS, Redirección 301 y Jaulas Chroot

* **En pantalla:** Diagrama de arquitectura de la Tarea 4.2 o terminal lista en Debian Server.
* **Lo que dices verbalmente:**
  > *"Antes de comenzar la instalación, repasemos brevemente tres conceptos teóricos cruciales para entender el porqué de cada paso:*
  > 
  > *Primero: **¿Cómo funciona el cifrado TLS y qué es un certificado SAN?**  
  > En el cifrado HTTPS, el servidor presenta un certificado digital que contiene su clave pública. Antiguamente los certificados solo utilizaban el campo `Common Name` (CN), pero hoy en día los navegadores como Firefox y Chrome exigen la extensión **Subject Alternative Name (SAN)**. Esta extensión permite certificar múltiples identidades en un único certificado: por ejemplo, `empresa.local`, `www.empresa.local` y la IP numérica `192.168.50.10`.*
  > 
  > *Segundo: **La Redirección Permanente 301 en Apache**.  
  > En lugar de simplemente cerrar el puerto 80, levantamos un VirtualHost que recibe las peticiones HTTP y mediante el módulo de reescritura `mod_rewrite`, le responde al navegador con un código de estado `301 Moved Permanently`, indicándole que la ubicación definitiva del recurso es la versión segura con `https://`. Esto garantiza que los usuarios despistados que escriban la URL sin 'https' sean protegidos de forma automática y transparente.*
  > 
  > *Tercero: **El enjaulamiento Chroot Jail**.  
  > En Linux, la llamada al sistema `chroot` cambia la raíz del sistema de archivos percibida por un proceso específico. Cuando nuestro usuario FTP inicia sesión, el demonio `vsftpd` le dice al kernel: 'Para este proceso, la carpeta `/home/ftpuser` es la raíz absoluta `/`'. Si el usuario intenta ejecutar un comando como `cd ..` o `cd /etc`, el sistema operativo le responde que es imposible subir más allá de su raíz. De este modo, si un atacante comprometiera las credenciales de un usuario FTP, quedaría completamente atrapado sin posibilidad de leer el archivo de contraseñas `/etc/shadow` o infectar binarios del sistema.*
  > 
  > *Habiendo entendido estos principios de defensa en profundidad, iniciemos la implementación."*

---

## C. IMPLEMENTACIÓN PRÁCTICA PASO A PASO

### [04:30 - 05:45] Paso 1: Instalación de Paquetes del Stack LAMP y VSFTPD

* **En pantalla:** Terminal de `Debian Server`.
* **Lo que dices verbalmente:**
  > *"Nos encontramos en la terminal de nuestro Debian Server. Lo primero que haremos será instalar la pila completa de software: el servidor HTTP Apache, el motor de base de datos MariaDB, el intérprete de PHP con su módulo de conexión para bases de datos relacionales, la biblioteca criptográfica OpenSSL y el demonio FTP `vsftpd`.*
  > 
  > *Ejecutamos con permisos de administrador:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo apt update && sudo apt install -y apache2 mariadb-server php libapache2-mod-php php-mysql vsftpd openssl
  ```
* **Explicación técnica del comando:**
  > *"Desglosemos los paquetes que estamos solicitando:*
  > *- `apache2`: El servidor web más robusto y probado del mundo open source.*
  > *- `mariadb-server`: La bifurcación comunitaria de MySQL de alto rendimiento para la gestión de bases de datos relacionales.*
  > *- `php` y `libapache2-mod-php`: El motor que procesa las páginas dinámicas y el módulo que permite a Apache interpretar código PHP directamente en memoria.*
  > *- `php-mysql`: La extensión que provee los controladores de la biblioteca PDO y MySQLi para que nuestras aplicaciones PHP puedan interactuar con MariaDB.*
  > *- `vsftpd`: 'Very Secure FTP Daemon', diseñado específicamente desde su concepción para prevenir desbordamientos de búfer y vulnerabilidades de seguridad comunes en otros servidores FTP clásicos.*
  > *- `openssl`: La herramienta criptográfica con la que generaremos los pares de llaves y los certificados X.509.*
  > 
  > *La instalación concluye sin advertencias y los servicios quedan integrados en el sistema."*

---

### [05:45 - 07:30] Paso 2: Generación del Certificado SSL/TLS con Extensión SAN

* **En pantalla:** Terminal de Debian Server mostrando el script de generación de certificados.
* **Lo que dices verbalmente:**
  > *"Para habilitar HTTPS necesitamos un certificado digital. En un entorno de producción utilizaríamos una entidad certificadora reconocida como Let's Encrypt o DigiCert, pero en redes corporativas privadas o de laboratorio, el procedimiento estándar de la industria es emitir un certificado autofirmado con extensión SAN.*
  > 
  > *Para no cometer errores en la línea de comandos de OpenSSL, creamos un script automatizado llamado `generar_certificados.sh` dentro de nuestro repositorio:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cd ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.2/
  cat generar_certificados.sh
  ```
* **Explicación técnica del script `generar_certificados.sh`:**
  > *"Analicemos la lógica interna de este script:*
  > *1. Primero define las variables de destino seguras: el certificado público se guardará en `/etc/ssl/certs/empresa.local.crt` y la clave privada protegida en `/etc/ssl/private/empresa.local.key`.*
  > *2. Crea un archivo temporal de configuración para OpenSSL donde inyecta el bloque crítico `subjectAltName`:*
  > ```text
  > subjectAltName = @alt_names
  > [alt_names]
  > DNS.1 = empresa.local
  > DNS.2 = www.empresa.local
  > IP.1 = 192.168.50.10
  > ```
  > *3. Luego invoca el binario de OpenSSL:*
  > ```bash
  > openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  >   -keyout /etc/ssl/private/empresa.local.key \
  >   -out /etc/ssl/certs/empresa.local.crt \
  >   -config /tmp/san.cnf
  > ```
  > *- El argumento `req -x509` le indica que deseamos un certificado autofirmado estándar X.509.*
  > *- `-nodes` significa 'no DES', es decir, que la llave privada no tendrá una frase de paso cifrada, lo cual es indispensable para que el servidor web Apache pueda iniciar automáticamente en el arranque sin pedir una contraseña por teclado.*
  > *- `-days 365` establece una validez de un año.*
  > *- `rsa:2048` genera una clave asimétrica robusta de 2048 bits.*
  > *4. Y finalmente aplica permisos estrictos con `chmod 600` sobre la llave privada para que ningún usuario no privilegiado del sistema pueda leer la clave.*
  > 
  > *Ejecutamos el script con:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  chmod +x generar_certificados.sh && sudo ./generar_certificados.sh
  ```
* **Resultado:**
  > *- Observen el mensaje en verde: 'Certificados SSL generados exitosamente'. Nuestras credenciales criptográficas ya están en su lugar."*

---

### [07:30 - 09:15] Paso 3: Configuración de VirtualHosts en Apache (Puertos 80 y 443)

* **En pantalla:** Terminal de Debian Server mostrando los archivos de configuración de Apache.
* **Lo que dices verbalmente:**
  > *"Ahora configuraremos Apache mediante la arquitectura de Virtual Hosts (sitios virtuales). Necesitamos dos archivos en `/etc/apache2/sites-available/`.*
  > 
  > *El primero es `empresa.local.conf`, que atiende el puerto HTTP 80:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat /etc/apache2/sites-available/empresa.local.conf
  ```
* **Explicación técnica de la redirección 301:**
  > *"Veamos qué contiene este bloque:*
  > ```apache
  > <VirtualHost *:80>
  >     ServerName empresa.local
  >     ServerAlias www.empresa.local
  >     RewriteEngine On
  >     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  > </VirtualHost>
  > ```
  > *- `ServerName` y `ServerAlias` definen los nombres que atenderá este host virtual.*
  > *- `RewriteEngine On` activa el motor de reescritura de URLs de Apache.*
  > *- `RewriteRule`: toma cualquier petición entrante (`^(.*)$`) y la redirige hacia el mismo host y URI pero anteponiendo `https://`.*
  > *- La bandera `[L,R=301]` significa: 'L' (Last rule, no apliques más reglas) y 'R=301' (emite una respuesta HTTP 301 de redirección permanente). Esto es vital para SEO y para que los navegadores recuerden la regla en caché.*
  > 
  > *Ahora veamos el VirtualHost seguro en el puerto 443 con `cat /etc/apache2/sites-available/empresa.local-ssl.conf`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat /etc/apache2/sites-available/empresa.local-ssl.conf
  ```
* **Explicación técnica de la configuración SSL:**
  > *- Aquí especificamos `<VirtualHost *:443>`.*
  > *- `DocumentRoot /var/www/html/empresa.local`: indica la carpeta del disco donde se alojan nuestros archivos web.*
  > *- `SSLEngine on`: activa el cifrado criptográfico.*
  > *- `SSLCertificateFile` y `SSLCertificateKeyFile`: vinculan el certificado y la llave que acabamos de generar con OpenSSL.*
  > *- `SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1`: directiva de seguridad fundamental. Desactiva explícitamente protocolos obsoletos y vulnerables como SSLv3, TLS 1.0 y TLS 1.1, forzando únicamente el uso de **TLS 1.2 y TLS 1.3**.*
  > 
  > *Para activar estos sitios y los módulos necesarios, ejecutamos:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  sudo a2enmod ssl rewrite
  sudo a2ensite empresa.local.conf empresa.local-ssl.conf
  sudo apache2ctl configtest
  ```
* **Explicación técnica:**
  > *- `a2enmod ssl rewrite`: habilita los módulos de cifrado y reescritura.*
  > *- `a2ensite`: crea los enlaces simbólicos en `sites-enabled`.*
  > *- `apache2ctl configtest`: compila y verifica que toda la sintaxis de Apache esté libre de errores. Observen la salida: nos responde **Syntax OK**."*

---

### [09:15 - 10:45] Paso 4: Base de Datos MariaDB y Portal Dinámico PHP

* **En pantalla:** Terminal de Debian Server mostrando MariaDB y los archivos PHP.
* **Lo que dices verbalmente:**
  > *"Ahora daremos vida a nuestra aplicación web conectándola con la base de datos MariaDB.*
  > 
  > *En la consola de MariaDB creamos una base de datos llamada `empresa_db`, una tabla `empleados` con registros de prueba (como administradores y desarrolladores web), y creamos un usuario dedicado `webuser` con contraseña segura `Empresa2026!` que solo tiene permisos de lectura sobre esta base de datos.*
  > 
  > *En nuestro directorio web `/var/www/html/empresa.local/` desplegamos dos archivos PHP:*
  > *1. `index.php`: Una interfaz corporativa visual que muestra en tiempo real:*
  > *- La confirmación del canal seguro HTTPS.*
  > *- La versión detectada del motor PHP en ejecución.*
  > *- La IP del cliente conectado y la IP del servidor.*
  > *- Y mediante un bloque `try-catch` con PDO, comprueba la conexión a MariaDB mostrando un recuadro verde de éxito.*
  > *2. `db_test.php`: Un endpoint que realiza una consulta SQL `SELECT * FROM empleados`, formatea los datos y los retorna como un servicio web moderno en formato **JSON**.*
  > 
  > *Reiniciamos el servicio de Apache y MariaDB para consolidar los cambios:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart apache2 mariadb
  sudo systemctl status apache2 mariadb --no-pager
  ```
* **Resultado:**
  > *- Ambos demonios web y base de datos aparecen en color verde en estado **active (running)**."*

---

### [10:45 - 12:15] Paso 5: Configuración y Enjaulamiento Chroot en VSFTPD

* **En pantalla:** Terminal de Debian Server mostrando `/etc/vsftpd.conf`.
* **Lo que dices verbalmente:**
  > *"Pasemos ahora al servidor de transferencia de archivos: VSFTPD.*
  > *Abramos su archivo de configuración con `cat /etc/vsftpd.conf` y revisemos las directivas de seguridad más críticas:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat /etc/vsftpd.conf
  ```
* **Explicación técnica de directivas de VSFTPD:**
  > *- `listen=YES` y `listen_ipv6=NO`: Le indican que atienda peticiones en IPv4 en el puerto estándar 21.*
  > *- `anonymous_enable=NO`: Rechaza cualquier conexión que no proporcione credenciales válidas.*
  > *- `local_enable=YES` y `write_enable=YES`: Permiten que los usuarios locales del sistema Linux puedan autenticarse y tengan permisos de escritura y subida.*
  > *- `local_umask=022`: Aplica una máscara de permisos que otorga permisos `644` a los archivos subidos (lectura y escritura para el dueño, lectura para los demás).*
  > *- `chroot_local_user=YES`: **¡Esta es la directiva estrella de la práctica!** Le ordena a vsftpd enjaular automáticamente a todos los usuarios locales en su directorio home.*
  > *- `allow_writeable_chroot=YES`: Permite que la jaula sea operativa sin requerir permisos de solo lectura en la raíz.*
  > *- `pasv_enable=YES`, `pasv_min_port=40000` y `pasv_max_port=40100`: Habilitan el modo pasivo de FTP limitando los puertos de datos a un rango controlado, ideal para atravesar cortafuegos empresariales.*
  > *- `pam_service_name=vsftpd`: Vincula la autenticación directamente al subsistema PAM de Debian.*
  > 
  > *Para las pruebas creamos un usuario dedicado llamado `ftpuser` con la contraseña `Alumno123`, y dentro de su carpeta personal creamos la subcarpeta `archivos` asegurando la propiedad con `chown -R ftpuser:ftpuser /home/ftpuser/archivos`.*
  > 
  > *Reiniciamos el servicio y validamos su estado:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart vsftpd
  sudo systemctl status vsftpd --no-pager
  ```
* **Resultado:**
  > *- El servicio `vsftpd` está corriendo impecablemente en el puerto 21. ¡Pasemos a la máquina cliente a verificar todo!"*

---

### [12:15 - 14:15] Paso 6: Pruebas y Validación desde el Cliente Debian XFCE

* **En pantalla:** Enfoque en la máquina de la derecha: `Debian XFCE Cliente`.
* **Lo que dices verbalmente:**
  > *"Nos encontramos en nuestra estación de trabajo cliente con Debian XFCE. Vamos a realizar todas las pruebas prácticas tanto del servidor web como del servidor FTP.*
  > 
  > *Prueba Web 1: Redirección automática HTTP a HTTPS.*
  > *Abrimos el navegador Mozilla Firefox y escribimos en la barra de direcciones la URL insegura: `http://www.empresa.local` y presionamos Enter.*
  > *¡Observen atentamente! De forma instantánea la URL cambia a `https://www.empresa.local`. El código de redirección 301 de Apache funcionó a la perfección.*
  > 
  > *Prueba Web 2: Inspección del Certificado SSL con SAN.*
  > *Hacemos clic en el icono del candado en la barra de direcciones y abrimos los detalles del certificado. Podemos apreciar que el emisor es `empresa.local` de la organización `Empresa Corporativa S.A.` y en la sección de nombres alternativos SAN figuran `empresa.local`, `www.empresa.local` y nuestra IP `192.168.50.10`.*
  > 
  > *Prueba Web 3: Conectividad LAMP y API JSON.*
  > *En la página principal vemos el banner azul con la confirmación de **HTTPS (Cifrado Activo)**, la versión de PHP y el recuadro verde que certifica: **Conectado exitosamente a MariaDB**.*
  > *Si abrimos una nueva pestaña con la dirección `https://www.empresa.local/db_test.php`, observen la salida en pantalla: nos devuelve una estructura JSON perfecta con los datos de los empleados extraídos en tiempo real desde la base de datos relacional.*
  > 
  > *Ahora pasamos a las pruebas del servidor FTP desde la terminal del cliente.*
  > 
  > *Prueba FTP 1: Bloqueo de acceso anónimo.*
  > *Ejecutamos en la terminal del cliente:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  ftp 192.168.50.10
  ```
  *(Al pedir usuario escribimos `anonymous` y presionamos Enter)*.
* **Resultado en consola:**
  > *- El servidor nos rechaza inmediatamente con `530 Login incorrect` o `Permission denied`. Ningún intruso puede entrar sin autorización.*
  > 
  > *Prueba FTP 2: Autenticación exitosa y demostración de la Jaula Chroot.*
  > *Volvemos a conectar e ingresamos con el usuario `ftpuser` y contraseña `Alumno123`:*
* **Comandos a ejecutar en la sesión FTP interactiva:**
  ```text
  ftp> pwd
  ftp> cd ..
  ftp> pwd
  ftp> cd /etc
  ```
* **Explicación técnica en pantalla:**
  > *- Observen la respuesta a `pwd`: nos indica `Remote directory: /`.*
  > *- Ejecutamos `cd ..` intentando salir a la raíz del disco host y volvemos a escribir `pwd`: seguimos exactamente en `/`.*
  > *- Si intentamos forzar la navegación hacia directorios confidenciales del sistema operativo ejecutando `cd /etc`, el servidor nos bloquea de tajo arrojando el código: `550 Failed to change directory`.*
  > *¡El usuario está 100% encarcelado en su jaula Chroot! Para él, el resto del servidor Linux simplemente no existe.*
  > 
  > *Prueba FTP 3: Transferencia de archivos.*
  > *Entramos a su subcarpeta permitida ejecutando `cd archivos`. Subimos un archivo con `put` y descargamos con `get`. Si abrimos el cliente gráfico **FileZilla** en Debian XFCE, nos conectamos a `192.168.50.10` con `ftpuser` y vemos en el panel derecho exactamente la misma estructura, logrando transferir archivos bidireccionalmente con éxito total."*

---

## D. CONCLUSIÓN Y MEJORES PRÁCTICAS

### [14:15 - 15:30] Escena 7: Resumen Técnico y Cierre del Video

* **En pantalla:** Cámara web en primer plano con el escritorio y las verificaciones exitosas de fondo.
* **Lo que dices verbalmente:**
  > *"En este tutorial hemos consolidado una arquitectura web y de transferencia de datos robusta, escalable y siguiendo los estándares de seguridad de la industria:*
  > *1. Desplegamos el stack **LAMP**, implementando redirecciones automáticas permanentes **301** y protegiendo el tráfico con certificados **TLS 1.2 y 1.3 con extensión SAN**.*
  > *2. Integramos la persistencia de datos mediante **MariaDB y PHP con PDO**, validando la comunicación y consumiendo datos en formato JSON.*
  > *3. Securizamos el servicio **VSFTPD**, anulando conexiones anónimas y aplicando la técnica de contención **Chroot Jail**, garantizando que los usuarios jamás puedan vulnerar el sistema operativo del servidor.*
  > 
  > *En un entorno de producción real en internet, se recomienda utilizar certificados emitidos por autoridades comerciales o Let's Encrypt y complementar el servicio FTP con SFTP sobre SSH para cifrar también el canal de comandos de archivos.*
  > 
  > *Todos los archivos de configuración, scripts de automatización de certificados y reportes técnicos con capturas de pantalla están a su disposición en el repositorio de GitHub en la descripción.*
  > 
  > *Muchas gracias por acompañarme. Si les gustó este tutorial, denle like, suscríbanse y nos vemos en la Tarea 4.3 para desplegar un servidor de correo corporativo completo con Postfix y Dovecot. ¡Hasta la próxima!"*
