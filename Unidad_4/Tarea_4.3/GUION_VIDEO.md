# Guion Maestro del Video Tutorial: Tarea 4.3 (Servidor de Correo Corporativo Postfix MTA + Dovecot MDA)

> **Perfil del Presentador:** Ingeniero Senior en Sistemas y Administrador de Correo Electrónico Linux.  
> **Formato de Grabación:** OBS Studio con captura de pantalla y **cámara web activa en la esquina inferior derecha** en todo momento.  
> **Disposición de Pantalla:** Dos máquinas virtuales en VMware Workstation:
> - **Lado Izquierdo:** `Debian Server` (Terminal en tiempo real monitoreando logs). IP: `192.168.50.10`.
> - **Lado Derecho:** `Debian XFCE Cliente` (Escritorio con Mozilla Thunderbird y consola). IP: `192.168.50.25`.
> **Segmento de Red:** VMware LAN Segment aislado `Lab-Red` (`192.168.50.0/24`).  
> **Duración aproximada:** 14 a 18 minutos (Tutorial didáctico de nivel avanzado).

---

## ESTRUCTURA DEL TUTORIAL

```
├── A. INTRODUCCIÓN Y ARQUITECTURA (00:00 - 02:30)
│   ├── Presentación y el reto de administrar correo en Linux
│   ├── La tríada del correo: MTA (Postfix), MDA (Dovecot) y MUA (Thunderbird)
│   └── Requisitos previos y entorno de laboratorio
│
├── B. FUNDAMENTOS TÉCNICOS ESENCIALES (02:30 - 05:00)
│   ├── Protocolos de transporte y buzón: SMTP, Submission e IMAP sobre TLS
│   ├── Comparativa de almacenamiento: Mbox tradicional vs Maildir/ moderno
│   └── El rol vital del DNS: Registros MX y políticas de origen SPF
│
├── C. IMPLEMENTACIÓN PRÁCTICA PASO A PASO (05:00 - 15:00)
│   ├── Paso 1: Instalación de Postfix, Dovecot y utilidades de correo
│   ├── Paso 2: Configuración del nombre del correo (/etc/mailname)
│   ├── Paso 3: Configuración profunda de Postfix MTA (main.cf y master.cf)
│   ├── Paso 4: Configuración unificada de Dovecot 2.4 MDA (dovecot.conf)
│   ├── Paso 5: Automatización de usuarios y buzones con crear_buzones.sh
│   ├── Paso 6: Verificación de registros DNS (MX y SPF) desde el Cliente
│   ├── Paso 7: Envío de correo con Swaks y trazabilidad de logs en tiempo real
│   ├── Paso 8: Inspección del mensaje físico en el sistema de archivos Maildir
│   └── Paso 9: Configuración y prueba bidireccional en Mozilla Thunderbird
│
└── D. CONCLUSIÓN GENERAL DE LA UNIDAD 4 (15:00 - 17:00)
    ├── Síntesis de los servicios implementados (DHCP, DNS, Web, FTP y Correo)
    └── Repositorio GitHub, reportes técnicos y despedida
```

---

## A. INTRODUCCIÓN Y ARQUITECTURA

### [00:00 - 02:30] Escena 1: Presentación, Arquitectura de Correo y Resultado Final

* **En pantalla:** Escritorio de VMware en pantalla completa. A la izquierda la terminal negra de `Debian Server`; a la derecha el escritorio gráfico de `Debian XFCE` con Mozilla Thunderbird abierto. Cámara web visible en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola a todos y bienvenidos a este video tutorial avanzado sobre Administración de Servidores Linux. Mi nombre es [Tu Nombre Completo]. Hoy vamos a construir uno de los servicios más fascinantes, pero a la vez más respetados por cualquier administrador de sistemas: un **Servidor de Correo Electrónico Corporativo Completo y Seguro** bajo el dominio `empresa.local` utilizando **Postfix** y **Dovecot** sobre **Debian GNU/Linux 12**.*
  > 
  > *A diferencia de un servidor web donde un solo programa hace todo el trabajo, el correo electrónico opera mediante una división de responsabilidades muy precisa entre tres actores principales:*
  > *1. El **MTA** (Mail Transfer Agent): El cartero que viaja por la red transportando los sobres. Para este rol utilizaremos **Postfix**, encargado de recibir y enrutar mensajes mediante el protocolo **SMTP**.*
  > *2. El **MDA** (Mail Delivery Agent): El casillero postal que organiza las cartas en el buzón físico de cada usuario y le permite leerlas mediante el protocolo **IMAP**. Para esto utilizaremos **Dovecot** en su versión moderna 2.4.*
  > *3. El **MUA** (Mail User Agent): El cliente visual que utiliza el usuario final para redactar y consultar sus mensajes. En nuestro cliente Debian XFCE utilizaremos **Mozilla Thunderbird**, y en consola utilizaremos la navaja suiza de pruebas **Swaks**.*
  > 
  > *Al terminar este tutorial, habremos conectado Postfix con Dovecot mediante sockets de autenticación SASL, almacenaremos los correos en formato no monolítico **Maildir/**, blindaremos las conexiones con **cifrado TLS**, validaremos los registros **MX y SPF** en nuestro servidor DNS BIND9, y analizaremos la trazabilidad de entrega en tiempo real en los logs del sistema.*
  > 
  > *Trabajaremos en nuestro laboratorio aislado en VMware sobre el LAN Segment `Lab-Red`, con nuestro servidor Debian en la IP `192.168.50.10` y nuestro cliente Debian XFCE en la IP `192.168.50.25`. ¡Acompáñenme a la consola!"*

---

## B. FUNDAMENTOS TÉCNICOS ESENCIALES

### [02:30 - 05:00] Escena 2: Mbox vs Maildir, Protocolos SMTP/IMAP y Registros MX/SPF

* **En pantalla:** Diagrama de flujo de mensajería (secuencia del reporte técnico) o terminal del servidor preparada.
* **Lo que dices verbalmente:**
  > *"Antes de configurar los demonios, debemos tener muy claras tres decisiones de arquitectura indispensables:*
  > 
  > *Primera decisión: **¿Por qué utilizamos el formato Maildir en lugar de Mbox?**  
  > En el formato antiguo Mbox, todos los correos de un usuario se amontonaban dentro de un único archivo gigante en `/var/mail/usuario`. Si dos procesos intentaban escribir o leer al mismo tiempo, el archivo se bloqueaba con un 'file-lock' o peor aún, se corrompía por completo.  
  > Para resolver esto, hoy utilizaremos **Maildir/**. En Maildir, cada correo electrónico es un archivo independiente con un identificador único almacenado dentro de la carpeta personal del usuario en tres subdirectorios:*
  > *- `tmp`: para mensajes en proceso de entrega.*
  > *- `new`: para mensajes recién llegados no leídos.*
  > *- `cur`: para mensajes ya leídos.*
  > *Esto garantiza cero bloqueos, máxima velocidad y tolerancia a fallos.*
  > 
  > *Segunda decisión: **La matriz de puertos y protocolos seguros**.  
  > No utilizaremos conexiones en texto plano. Operaremos con:*
  > *- Puerto TCP 25: SMTP tradicional para la transferencia entre servidores MTA.*
  > *- Puerto TCP 587: Submission con STARTTLS, donde los usuarios entregan sus correos tras autenticarse con SASL.*
  > *- Puerto TCP 993: IMAPS (IMAP sobre SSL/TLS directo), donde el cliente Thunderbird consulta su bandeja de entrada de forma 100% cifrada.*
  > 
  > *Tercera decisión: **La integración con DNS (MX y SPF)**.  
  > Un servidor de correo no puede funcionar sin su base en el DNS. En nuestro BIND9 configuramos:*
  > *- El registro **MX** (Mail Exchanger) con prioridad 10 apuntando a `mail.empresa.local`, que le dice al mundo quién recibe los correos de `@empresa.local`.*
  > *- Y el registro **TXT** con política **SPF** (`v=spf1 mx ip4:192.168.50.10 ~all`), que certifica que únicamente nuestra IP tiene autorización legal para enviar correos a nombre de la empresa, evitando la falsificación de identidad o spoofing.*
  > 
  > *Comprendida la teoría, iniciemos la instalación práctica."*

---

## C. IMPLEMENTACIÓN PRÁCTICA PASO A PASO

### [05:00 - 06:45] Paso 1: Instalación de Postfix, Dovecot y Utilidades de Correo

* **En pantalla:** Consola de Debian Server.
* **Lo que dices verbalmente:**
  > *"Comenzamos instalando los paquetes necesarios en nuestro Debian Server. Ejecutamos:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo apt update && sudo apt install -y postfix dovecot-imapd dovecot-pop3d mailutils swaks
  ```
* **Explicación técnica del comando y del instalador:**
  > *"Desglosemos lo que estamos instalando:*
  > *- `postfix`: El Agente de Transferencia de Correo (MTA) de alto rendimiento, seguro y modular.*
  > *- `dovecot-imapd` y `dovecot-pop3d`: Los demonios que proporcionan los servicios de buzón IMAP y POP3.*
  > *- `mailutils`: Herramientas tradicionales de línea de comandos para manejo de correo en Linux.*
  > *- `swaks`: La navaja suiza de pruebas SMTP ('Swiss Army Knife for SMTP'), que nos permite simular diálogos SMTP interactivos con precisión quirúrgica.*
  > 
  > *¡Mucha atención aquí! Durante la instalación de Postfix, el instalador desplegará una pantalla azul interactiva en la consola:*
  > *1. En **Tipo general de configuración de correo**, seleccionamos con la tecla Tabulador **Sitio de Internet** (*Internet Site*) y presionamos Enter.*
  > *2. En **Nombre de correo del sistema**, escribimos el dominio corporativo: `empresa.local`.*
  > 
  > *Para asegurar que todas las herramientas reconozcan este nombre, consolidamos el archivo `/etc/mailname`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  echo "empresa.local" | sudo tee /etc/mailname
  ```
* **Explicación técnica:**
  > *- El comando `tee` con privilegios `sudo` escribe la cadena `empresa.local` directamente en `/etc/mailname`, que es el archivo estándar en Debian de donde Postfix y las utilidades locales leen el dominio de origen."*

---

### [06:45 - 09:15] Paso 2: Configuración Profunda de Postfix MTA (`main.cf`)

* **En pantalla:** Terminal de Debian Server mostrando `/etc/postfix/main.cf`.
* **Lo que dices verbalmente:**
  > *"Ahora configuraremos el corazón de Postfix: el archivo `/etc/postfix/main.cf`. En nuestro repositorio ya tenemos el archivo optimizado y comentado.*
  > 
  > *Lo copiamos a su ubicación en el sistema y revisamos sus directivas más importantes:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  cd ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.3/
  sudo cp main.cf /etc/postfix/main.cf
  sudo cp master.cf /etc/postfix/master.cf
  cat /etc/postfix/main.cf
  ```
* **Explicación técnica de directivas de Postfix:**
  > *"Analicemos línea por línea las directivas que convierten a este servidor en un nodo corporativo de nivel productivo:*
  > *- `myhostname = mail.empresa.local` y `mydomain = empresa.local`: Definen el FQDN del servidor y el dominio raíz de correo.*
  > *- `myorigin = /etc/mailname`: Asegura que cualquier correo generado localmente lleve el remitente `@empresa.local`.*
  > *- `mydestination = $myhostname, empresa.local, localhost.empresa.local, localhost`: Lista exhaustiva de los dominios para los cuales Postfix aceptará mensajes y los procesará para entrega local en lugar de retransmitirlos hacia afuera.*
  > *- `mynetworks = 127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128 192.168.50.0/24`: Define las redes de confianza autorizadas a enviar correo a través de este servidor. Incluye localhost y toda nuestra subred `192.168.50.0/24`.*
  > 
  > *Atención a este bloque crítico: el almacenamiento Maildir.*
  > ```text
  > home_mailbox = Maildir/
  > ```
  > *- La barra final `/` es determinante: le indica a Postfix que no cree un archivo mbox plano, sino una estructura de directorios Maildir en el home de cada usuario.*
  > 
  > *Cifrado TLS:*
  > ```text
  > smtpd_tls_cert_file = /etc/ssl/certs/empresa.local.crt
  > smtpd_tls_key_file = /etc/ssl/private/empresa.local.key
  > smtpd_tls_security_level = may
  > smtpd_tls_protocols = !SSLv2, !SSLv3, !TLSv1, !TLSv1.1
  > ```
  > *- Reutilizamos el certificado digital SAN generado con OpenSSL en la Tarea 4.2 y deshabilitamos protocolos criptográficos vulnerables.*
  > 
  > *Y finalmente, la integración SASL con Dovecot:*
  > ```text
  > smtpd_sasl_type = dovecot
  > smtpd_sasl_path = private/auth
  > smtpd_sasl_auth_enable = yes
  > smtpd_recipient_restrictions = permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination
  > ```
  > *- `smtpd_sasl_path = private/auth`: En lugar de reinventar una base de datos de contraseñas, Postfix delega la autenticación a Dovecot a través de un socket UNIX interno.*
  > *- `reject_unauth_destination`: Directiva de seguridad primordial que evita que nuestro servidor se convierta en un 'Open Relay' utilizado por spammers en internet."*

---

### [09:15 - 11:00] Paso 3: Configuración Unificada de Dovecot 2.4 MDA (`dovecot.conf`)

* **En pantalla:** Terminal de Debian Server mostrando `/etc/dovecot/dovecot.conf`.
* **Lo que dices verbalmente:**
  > *"Ahora configuraremos el agente de entrega y buzón: Dovecot. En Debian 12 / 13 contamos con la versión moderna **Dovecot 2.4**, la cual introdujo cambios sustanciales en su sintaxis y desaprobó directivas antiguas.*
  > 
  > *Para garantizar total estabilidad, implementamos un archivo unificado y limpio en `/etc/dovecot/dovecot.conf`:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  sudo cp dovecot.conf /etc/dovecot/dovecot.conf
  cat /etc/dovecot/dovecot.conf
  ```
* **Explicación técnica de directivas de Dovecot 2.4:**
  > *"Revisemos las directivas de Dovecot 2.4:*
  > *- Las dos primeras líneas obligatorias:*
  > ```text
  > dovecot_config_version = 2.4.0
  > dovecot_storage_version = 2.4.0
  > ```
  > *Dovecot 2.4 exige declarar explícitamente la versión de configuración para validar las reglas de sintaxis aplicables.*
  > *- `protocols = imap pop3`: Habilita los demonios para que los clientes puedan recuperar su correo.*
  > *- `passdb pam` y `userdb passwd`: Le indican a Dovecot que valide las credenciales contra el subsistema PAM y los usuarios locales de Linux.*
  > *- Almacenamiento Maildir en Dovecot 2.4:*
  > ```text
  > mail_driver = maildir
  > mail_path = ~/Maildir
  > ```
  > *Esta es la nueva sintaxis que sustituye a la antigua directiva `mail_location`.*
  > *- Cifrado SSL:*
  > ```text
  > ssl = yes
  > ssl_server_cert_file = /etc/ssl/certs/empresa.local.crt
  > ssl_server_key_file = /etc/ssl/private/empresa.local.key
  > ```
  > *- Y el socket SASL que conecta con Postfix:*
  > ```text
  > service auth {
  >   unix_listener /var/spool/postfix/private/auth {
  >     mode = 0660
  >     user = postfix
  >     group = postfix
  >   }
  > }
  > ```
  > *Crea el socket UNIX dentro del entorno chroot de Postfix otorgándole permisos de lectura y escritura al usuario del servicio de correo.*
  > 
  > *Validamos la configuración de Dovecot ejecutando:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo dovecot -n
  ```
* **Resultado:**
  > *- `dovecot -n` imprime la configuración cargada limpia sin errores sintácticos."*

---

### [11:00 - 12:30] Paso 4: Creación Automatizada de Usuarios y Buzones Maildir

* **En pantalla:** Terminal de Debian Server mostrando el script `crear_buzones.sh`.
* **Lo que dices verbalmente:**
  > *"Para probar el flujo de entrega necesitamos al menos dos cuentas de correo: un remitente y un destinatario. Diseñamos un script Bash llamado `crear_buzones.sh` que automatiza la creación de usuarios, establece sus contraseñas y genera la jerarquía de carpetas Maildir.*
  > 
  > *Veamos su funcionamiento ejecutándolo en consola:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  chmod +x crear_buzones.sh && sudo ./crear_buzones.sh
  ```
* **Explicación técnica del script `crear_buzones.sh`:**
  > *- El script crea las cuentas de sistema `usuario1` y `usuario2`.*
  > *- Aplica la contraseña estandarizada `Alumno123` mediante el comando `chpasswd`.*
  > *- Para cada usuario crea con `mkdir -p` las tres subcarpetas: `cur`, `new` y `tmp` dentro de `/home/<usuario>/Maildir/`.*
  > *- Aplica `chmod -R 700 /home/<usuario>/Maildir/`: esto es fundamental por privacidad y seguridad en Linux, garantizando que únicamente el usuario dueño o el administrador puedan leer los correos.*
  > *- Aplica `chown -R <usuario>:<usuario>` para que el demonio local de entrega pueda depositar los archivos sin errores de permisos.*
  > 
  > *Ahora reiniciamos Postfix y Dovecot y comprobamos su estado:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart postfix dovecot
  sudo systemctl status postfix dovecot --no-pager
  ```
* **Resultado:**
  > *- Ambos demonios aparecen en color verde en estado **active (running)**. Postfix y Dovecot están plenamente sincronizados y operativos."*

---

### [12:30 - 15:00] Paso 5: Pruebas de Envío, Trazabilidad en Tiempo Real y Buzón Físico

* **En pantalla:** Pantalla dividida en VMware. A la izquierda la terminal del servidor monitoreando logs en vivo; a la derecha la terminal del cliente Debian XFCE.
* **Lo que dices verbalmente:**
  > *"Ha llegado el momento más esperado: comprobar la entrega de correo y su trazabilidad de punta a punta.*
  > 
  > *Primero, verifiquemos desde el cliente que nuestro servidor DNS resuelva los registros indispensables para el correo:*
* **Comandos a ejecutar en el Cliente Debian XFCE:**
  ```bash
  dig @192.168.50.10 empresa.local MX +short
  nslookup mail.empresa.local 192.168.50.10
  dig @192.168.50.10 empresa.local TXT +short
  ```
* **Interpretación del resultado:**
  > *- El registro MX nos devuelve `10 mail.empresa.local.`.*
  > *- El registro A confirma la IP `192.168.50.10`.*
  > *- Y el registro TXT nos confirma la directiva de seguridad SPF: `"v=spf1 mx ip4:192.168.50.10 ~all"`.*
  > 
  > *Ahora, en la terminal de nuestro servidor Debian dejamos corriendo el monitoreo de eventos en vivo con `journalctl`:*
* **Comando a ejecutar en Debian Server (Terminal 1):**
  ```bash
  sudo journalctl -u postfix -u dovecot -f
  ```
* **Lo que dices verbalmente:**
  > *- El flag `-u` filtra los logs por unidad de servicio y `-f` mantiene el seguimiento en vivo ('follow').*
  > 
  > *Ahora abrimos otra terminal y lanzamos un correo de prueba desde `usuario1` hacia `usuario2` utilizando la herramienta `swaks`:*
* **Comando a ejecutar (en Terminal 2):**
  ```bash
  swaks --to usuario2@empresa.local \
        --from usuario1@empresa.local \
        --server 192.168.50.10 \
        --header "Subject: Prueba de Entrega - Unidad 4 Tarea 4.3" \
        --body "Hola usuario2, este es un mensaje de prueba para verificar la entrega local en Maildir."
  ```
* **Análisis de la transacción SMTP en pantalla:**
  > *- Observemos la negociación SMTP que despliega `swaks`:*
  > *1. Conecta al puerto 25 y el servidor responde: `220 mail.empresa.local ESMTP Postfix`.*
  > *2. Envía `EHLO` y Postfix anuncia sus capacidades: `PIPELINING`, `STARTTLS`, `AUTH PLAIN LOGIN`.*
  > *3. `MAIL FROM:<usuario1@empresa.local>` -> Postfix responde `250 2.1.0 Ok`.*
  > *4. `RCPT TO:<usuario2@empresa.local>` -> Postfix valida al destinatario y responde `250 2.1.5 Ok`.*
  > *5. `DATA` -> Envía cabeceras y cuerpo, finalizando con un punto solo en una línea.*
  > *6. Respuesta final de Postfix: `250 2.0.0 Ok: queued as 3ECDAA8161`.*
  > 
  > *¡Ahora miren inmediatamente la pantalla de la izquierda con el log del servidor!*
  > *Veamos la secuencia de eventos que ocurrió en una fracción de segundo:*
  > *1. `postfix/smtpd: connect from ...`*
  > *2. `postfix/cleanup: message-id=...`*
  > *3. `postfix/qmgr: from=<usuario1@empresa.local>, size=..., nrcpt=1 (queue active)`*
  > *4. **Y aquí está la línea clave:** `postfix/local[...]: to=<usuario2@empresa.local>, relay=local, delay=0.03, status=sent (delivered to maildir)`*
  > *5. `postfix/qmgr: removed`*
  > *¡El mensaje `status=sent (delivered to maildir)` es la certificación definitiva de que el correo fue aceptado, enrutado y entregado físicamente!*
  > 
  > *Comprobemos el archivo real en el sistema de archivos del servidor ejecutando con `sudo`:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  sudo ls -la /home/usuario2/Maildir/new/
  sudo sh -c 'cat /home/usuario2/Maildir/new/*'
  ```
* **Explicación técnica:**
  > *- Vemos que dentro de `/home/usuario2/Maildir/new/` se generó un archivo individual propiedad de `usuario2`. Al hacerle `cat`, observamos todas las cabeceras estándar del correo: `Date:`, `To:`, `From:`, `Subject:` y el cuerpo del mensaje intacto."*

---

### [15:00 - 17:00] Paso 6: Verificación Gráfica y Bidireccional en Mozilla Thunderbird

* **En pantalla:** Enfoque en `Debian XFCE Cliente` con Mozilla Thunderbird en primer plano.
* **Lo que dices verbalmente:**
  > *"Para cerrar con broche de oro, comprobaremos cómo el usuario final interactúa con el sistema utilizando un cliente gráfico estándar: **Mozilla Thunderbird** en Debian XFCE.*
  > 
  > *Configuración de la cuenta:*
  > *1. Abrimos Thunderbird y agregamos una cuenta de correo:*
  > *- Nombre: `Usuario 2`*
  > *- Dirección: `usuario2@empresa.local`*
  > *- Contraseña: `Alumno123`*
  > *2. En **Configuración Manual**:*
  > *- Servidor de Entrada (IMAP): `mail.empresa.local`, Puerto `993` con seguridad **SSL/TLS**, método de autenticación **Contraseña normal**.*
  > *- Servidor de Salida (SMTP): `mail.empresa.local`, Puerto `587` con seguridad **STARTTLS** (o puerto `25` sin cifrado para pruebas locales).*
  > *3. Aceptamos el certificado autofirmado marcando la excepción de seguridad.*
  > 
  > *Demostración de lectura y respuesta:*
  > *- Hacemos clic en la **Bandeja de Entrada (Inbox)**: ¡Miren cómo Dovecot sincroniza instantáneamente el mensaje que enviamos con Swaks!*
  > *- Abrimos el correo y leemos el asunto 'Prueba de Entrega - Unidad 4 Tarea 4.3'.*
  > *- Hacemos clic en el botón **Responder** de Thunderbird. Escribimos en el cuerpo del mensaje: 'Mensaje Recibido Bro' y presionamos **Enviar**.*
  > *- Observamos cómo el mensaje viaja por el puerto SMTP de Postfix, se registra en la carpeta **Enviados (Sent)** de Thunderbird y llega al buzón de `usuario1`.*
  > *¡La comunicación entre usuarios es completamente bidireccional, fluida y segura!"*

---

## D. CONCLUSIÓN GENERAL DE LA UNIDAD 4

### [17:00 - 18:30] Escena 7: Síntesis Integral de la Unidad 4 y Cierre del Video

* **En pantalla:** Cámara web en primer plano con el entorno completo de dos máquinas virtuales en VMware de fondo.
* **Lo que dices verbalmente:**
  > *"Con esta demostración culminamos con éxito rotundo la Tarea 4.3 y la totalidad de la Unidad 4 de Administración de Servidores.*
  > 
  > *Hagamos un breve recuento de la infraestructura integral que logramos construir a lo largo de esta unidad:*
  > *1. **Servicios de Red Base (Tarea 4.1):** Desplegamos **ISC-DHCP-Server** con rangos dinámicos, exclusiones para equipamiento crítico y reservas por dirección MAC; y **BIND9** con zonas directa, inversa y reenviadores recursivos.*
  > *2. **Servicios Web y Transferencia Segura (Tarea 4.2):** Implementamos el stack **LAMP (Apache, MariaDB, PHP)** con redirección forzada **301**, cifrado **TLS con certificados SAN**, consumo de base de datos relacional y servidor **VSFTPD securizado mediante Chroot Jail**.*
  > *3. **Servicios de Correo Electrónico Corporativo (Tarea 4.3):** Desplegamos el agente de transferencia **Postfix MTA** integrado mediante sockets SASL con **Dovecot 2.4 MDA**, almacenamiento no monolítico **Maildir/**, validación de políticas **MX y SPF**, y acceso seguro a través del cliente gráfico **Thunderbird**.*
  > 
  > *Cada uno de estos pasos, comandos, scripts comentados, reportes técnicos con capturas de pantalla y diagramas de arquitectura se encuentran completamente documentados y disponibles en el repositorio público de GitHub que les comparto en la descripción de este video.*
  > 
  > *Agradezco enormemente su atención y el tiempo dedicado a este tutorial. Si el contenido les fue útil, no olviden apoyarme con un like, suscribirse al canal y compartirlo. Mi nombre es [Tu Nombre Completo], un saludo para todos y nos vemos en el próximo proyecto de infraestructura y redes. ¡Hasta pronto!"*
