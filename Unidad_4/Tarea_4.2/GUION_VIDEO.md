# Guion del Video Demostrativo: Tarea 4.2 (Stack Web HTTPS y FTP vsftpd)

> **Instrucciones para la grabación:**
> - Software: **OBS Studio**.
> - **Cámara Web:** Activa y visible en la **esquina inferior derecha** en todo momento.
> - **Entorno en Pantalla:** Dos ventanas de VMware:
>   - Izquierda: **Debian Server** (consola).
>   - Derecha: **Debian XFCE** (escritorio gráfico con navegador Firefox y FileZilla).
> - **Duración estimada:** 4 a 6 minutos.
> - **Tono:** Didáctico, claro y profesional.

---

### [00:00 - 00:35] Escena 1: Presentación de la Tarea
* **En pantalla:** Escritorio mostrando las dos máquinas virtuales y cámara web en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola a todos. Mi nombre es [Tu Nombre Completo]. En este video presentaremos la Tarea 4.2 de la materia Administración de Servidores.*
  > 
  > *En esta práctica desplegaremos dos componentes fundamentales para cualquier infraestructura de servicios: primero, un **Stack Web Completo** con Apache2, soporte para lenguaje dinámico PHP y base de datos MariaDB, protegido bajo el protocolo seguro **HTTPS** con certificados SSL/TLS; y segundo, un servidor de transferencia de archivos **vsftpd** securizado con enjaulamiento **Chroot Jail** para aislar a los usuarios y evitar cualquier fuga de información del sistema operativo.*
  > 
  > *Nuevamente operamos con nuestro **Debian Server** a la izquierda y nuestra estación cliente **Debian XFCE** a la derecha."*

---

### [00:35 - 01:45] Escena 2: Inspección Técnica en Debian Server
* **En pantalla:** Terminal de Debian Server.
* **Acción:**
  1. Muestras la configuración del VirtualHost SSL:
     `cat /etc/apache2/sites-available/empresa.local-ssl.conf`
  2. Muestras las directivas de vsftpd:
     `grep -E "anonymous_enable|chroot_local_user|allow_writeable_chroot" /etc/vsftpd.conf`
* **Lo que dices verbalmente:**
  > *"Comencemos revisando brevemente la configuración en nuestro servidor Debian.*
  > 
  > *En Apache creamos un Virtual Host escuchando en el puerto 443 SSL. Aquí definimos nuestro DocumentRoot, habilitamos el motor `SSLEngine` y vinculamos nuestro certificado público y clave privada generados con OpenSSL, además de restringir los protocolos únicamente a TLS 1.2 y TLS 1.3.*
  > 
  > *Por parte del servidor de archivos vsftpd, destacamos dos directivas cruciales para la seguridad: `anonymous_enable=NO` para bloquear intrusos sin cuenta, y `chroot_local_user=YES`, que es la directiva encargada de crear la jaula de seguridad para que ningún usuario pueda navegar fuera de su carpeta personal."*

---

### [01:45 - 03:15] Escena 3: Demostración de Navegación Web Segura (HTTPS)
* **En pantalla:** Navegador Firefox en Debian XFCE Cliente.
* **Acción:**
  1. Escribes en la barra de direcciones: `http://www.empresa.local` y presionas Enter.
  2. Muestras cómo se redirige automáticamente a `https://www.empresa.local`.
  3. Abres los detalles del certificado haciendo clic en el candado.
  4. Muestras en la página web: el estado HTTPS activo, versión de PHP y la conexión exitosa a MariaDB.
  5. Abres una pestaña con `https://www.empresa.local/db_test.php` para mostrar la consulta JSON a la base de datos.
* **Lo que dices verbalmente:**
  > *"Vamos a la máquina cliente con Debian XFCE. Abrimos Firefox e ingresamos con `http://` normal. Noten cómo la directiva de reescritura nos redirige automáticamente al puerto 443 seguro.*
  > 
  > *Hacemos clic en el candado del navegador para inspeccionar el certificado digital. Como pueden apreciar, fue emitido para `empresa.local`, firmado por nuestra propia autoridad corporativa y cuenta con los nombres alternativos SAN correspondientes.*
  > 
  > *En la página principal que desarrollamos en PHP, podemos ver en tiempo real la confirmación de la conexión cifrada, la versión de PHP en ejecución y la conexión exitosa al gestor de bases de datos MariaDB.*
  > 
  > *Si ingresamos al script `db_test.php`, observamos cómo el servidor web interactúa con MariaDB, consultando registros reales de una tabla corporativa y devolviéndolos en formato JSON. Con esto queda demostrada la integración completa del stack LAMP con HTTPS."*

---

### [03:15 - 04:45] Escena 4: Demostración de vsftpd y Jaula Chroot (Aislamiento)
* **En pantalla:** Terminal o FileZilla en Debian XFCE Cliente.
* **Acción:**
  1. Conexión anónima fallida:
     `ftp 192.168.50.10` -> usuario `anonymous` -> muestra rechazo `530 Permission denied`.
  2. Conexión de usuario local:
     `ftp 192.168.50.10` -> usuario `ftpuser` / password -> `230 User logged in`.
  3. Demostración de la jaula:
     `pwd`
     `cd ..`
     `pwd`
     `cd /etc`
  4. Subida y descarga de archivo:
     `cd archivos`
     `ls`
     `get leeme.txt`
     `put prueba_subida.txt`
     `ls`
* **Lo que dices verbalmente:**
  > *"Ahora pasamos al servidor de archivos vsftpd.*
  > 
  > *Primero intentamos iniciar sesión como usuario anónimo desde la terminal del cliente. El servidor nos responde inmediatamente con un error 530 de permiso denegado, confirmando que nadie puede acceder sin credenciales.*
  > 
  > *Ahora ingresamos con nuestro usuario local `ftpuser`. El inicio de sesión es exitoso.*
  > 
  > *Aquí viene la parte más importante: el enjaulamiento Chroot Jail. Si ejecutamos `pwd`, el servidor nos dice que estamos en la raíz `/`.*
  > *Si intentamos salir con `cd ..` o intentamos acceder a carpetas críticas del sistema operativo como `cd /etc` o `cd /var`, el sistema nos arroja error 550 y no nos permite salir. Para el usuario, no existe nada más allá de su carpeta designada. La seguridad del sistema queda garantizada.*
  > 
  > *Finalmente entramos a su subcarpeta `archivos`, descargamos el archivo `leeme.txt` con el comando `get` y subimos nuestro archivo de prueba `prueba_subida.txt` con el comando `put`. La transferencia se completa de manera íntegra."*

---

### [04:45 - 05:15] Escena 5: Conclusión
* **En pantalla:** Cámara web y escritorio.
* **Lo que dices verbalmente:**
  > *"Hemos comprobado satisfactoriamente el funcionamiento del servidor web Apache con cifrado SSL/TLS, integración PHP y MariaDB, así como el servidor de archivos vsftpd con bloqueo anónimo y aislamiento total por Chroot Jail.*
  > 
  > *Todas las configuraciones y evidencias se encuentran disponibles en el repositorio de GitHub y en el reporte técnico en PDF.*
  > 
  > *Muchas gracias por su atención."*
