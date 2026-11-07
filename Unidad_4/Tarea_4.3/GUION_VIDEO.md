# Guion Maestro de Demostración Técnica: Tarea 4.3 (Servidor de Correo Corporativo Postfix MTA + Dovecot MDA)

> **Perfil del Expositor:** Ingeniero en Sistemas y Administrador de Correo e Infraestructura de Servidores Linux.  
> **Tono y Estilo:** Formal, técnico, académico, riguroso y didáctico. Sin frases informales ni modismos de redes sociales.  
> **Formato de Grabación:** OBS Studio con captura de pantalla limpia y **cámara web activa en la esquina inferior derecha** durante toda la demostración.  
> **Disposición del Entorno en Pantalla:** Dos máquinas virtuales en VMware Workstation:
> - **Lado Izquierdo:** `Debian Server` (Terminal en tiempo real monitoreando eventos de logs). IP: `192.168.50.10`.
> - **Lado Derecho:** `Debian XFCE Cliente` (Estación de trabajo con Mozilla Thunderbird y consola). IP: `192.168.50.25`.
> **Segmento de Red:** VMware LAN Segment privado `Lab-Red` (`192.168.50.0/24`).  
> **Duración estimada:** 14 a 18 minutos.

---

## ESTRUCTURA DE LA DEMOSTRACIÓN

```
├── A. INTRODUCCIÓN Y ARQUITECTURA DE MENSAJERÍA (00:00 - 02:30)
│   ├── Identificación de la práctica y la complejidad del correo electrónico
│   ├── La tríada operativa: MTA (Postfix), MDA (Dovecot) y MUA (Thunderbird)
│   └── Topología de red y parámetros de interconexión en VMware
│
├── B. FUNDAMENTOS TÉCNICOS Y PROTOCOLOS (02:30 - 05:00)
│   ├── Comparativa de almacenamiento: Mbox monolítico vs Maildir/ no bloqueante
│   ├── Matriz de protocolos y puertos: SMTP (25), Submission (587) e IMAPS (993)
│   └── Cimientos en el DNS: Intercambiador MX y políticas de identidad SPF
│
├── C. DESARROLLO, ANÁLISIS LÍNEA POR LÍNEA Y PRUEBAS EN VIVO (05:00 - 15:30)
│   ├── Paso 1: Instalación de paquetes de correo y utilidades auxiliares
│   ├── Paso 2: Desglose exhaustivo línea por línea de Postfix (/etc/postfix/main.cf)
│   ├── Paso 3: Desglose exhaustivo línea por línea de Dovecot 2.4 (/etc/dovecot/dovecot.conf)
│   ├── Paso 4: Automatización de usuarios y estructura Maildir (crear_buzones.sh)
│   ├── Paso 5: Verificación de registros DNS (MX y SPF) desde el cliente
│   ├── Paso 6: Diálogo SMTP interactivo con Swaks y trazabilidad en logs
│   ├── Paso 7: Inspección física de archivos en el sistema de almacenamiento Maildir
│   └── Paso 8: Integración y flujo bidireccional en cliente MUA (Mozilla Thunderbird)
│
└── D. CONCLUSIONES GENERALES DE LA UNIDAD 4 (15:30 - 17:00)
    ├── Consolidación integral de servicios (DHCP, DNS, Web, FTP y Correo)
    └── Repositorio institucional en GitHub, memorias técnicas y cierre formal
```

---

## A. INTRODUCCIÓN Y ARQUITECTURA DE MENSAJERÍA

### [00:00 - 02:30] Escena 1: Presentación, Desacoplamiento de Roles y Arquitectura

* **En pantalla:** Escritorio de VMware mostrando las dos máquinas virtuales en paralelo: a la izquierda la consola de `Debian Server` y a la derecha el escritorio de `Debian XFCE`. Cámara web visible en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Qué tal a todos, mi nombre es [Tu Nombre], estudiante de la carrera de Ingeniería en Informática, y este video corresponde a la materia de Administración de Servidores.*
  > 
  > *Hoy vamos a desarrollar la Tarea 4.3 de la Unidad 4, enfocada en la implementación, configuración y auditoría de un **Servidor de Correo Electrónico Corporativo Completo y Seguro** bajo el dominio `empresa.local`, empleando **Postfix** como Agente de Transferencia de Correo (MTA) y **Dovecot** como Agente de Entrega de Correo (MDA) sobre **Debian GNU/Linux 12**.*
  > 
  > *A nivel arquitectónico, el correo electrónico exige desacoplar tres componentes indispensables:*
  > *1. El **MTA** (Mail Transfer Agent): Encargado del enrutamiento, transmisión y aceptación de mensajes a través de la red mediante el protocolo **SMTP**. Para este rol implementamos **Postfix**.*
  > *2. El **MDA** (Mail Delivery Agent): Responsable del almacenamiento físico de los mensajes en el disco del servidor y de proveer acceso seguro a los buzones mediante el protocolo **IMAP**. Para esto desplegamos **Dovecot 2.4**.*
  > *3. El **MUA** (Mail User Agent): La interfaz que utiliza el usuario final para redactar, enviar y consultar su correspondencia. En la estación cliente utilizaremos **Mozilla Thunderbird**, y en la consola emplearemos **Swaks** para pruebas de diagnóstico SMTP a bajo nivel.*
  > 
  > *Durante esta demostración conectaremos Postfix con Dovecot mediante sockets de autenticación SASL, implementaremos el formato de almacenamiento no monolítico **Maildir/**, aplicaremos **cifrado TLS**, validaremos los registros **MX y SPF** en BIND9, y analizaremos la trazabilidad del flujo de entrega en tiempo real en los registros del sistema.*
  > 
  > *El entorno opera en el LAN Segment `Lab-Red` (`192.168.50.0/24`), con el servidor en `192.168.50.10` y la estación cliente en `192.168.50.25`. Pasemos a revisar los fundamentos del protocolo."*

---

## B. FUNDAMENTOS TÉCNICOS Y PROTOCOLOS

### [02:30 - 05:00] Escena 2: Mbox vs Maildir, Matriz de Puertos y Registros MX/SPF

* **En pantalla:** Diagrama de secuencia del reporte técnico o terminal de Debian Server.
* **Lo que dices verbalmente:**
  > *"Analicemos tres decisiones técnicas de diseño fundamentales para este despliegue:*
  > 
  > *Primera decisión: **Formato de Almacenamiento Maildir frente a Mbox**.  
  > En el formato tradicional Mbox, la totalidad de los correos de un usuario se almacenaban en un único archivo de texto continuo en `/var/mail/`. Esta estructura presentaba graves problemas de concurrencia: si el demonio de entrega intentaba escribir al mismo tiempo que el cliente leía, se requerían bloqueos a nivel de archivo (*file-locking*) que ralentizaban el sistema o provocaban la corrupción total del buzón.  
  > Para resolverlo, implementamos el formato **Maildir/**. En Maildir, cada correo es un archivo independiente con un identificador único almacenado dentro de la carpeta personal del usuario en tres subdirectorios:*
  > *- `tmp`: para mensajes en proceso de recepción o composición.*
  > *- `new`: para mensajes recibidos que aún no han sido leídos por el cliente.*
  > *- `cur`: para mensajes que ya han sido leídos o procesados.*
  > *Esta arquitectura basada en el sistema de archivos POSIX permite operaciones atómicas, elimina condiciones de carrera y garantiza integridad absoluta.*
  > 
  > *Segunda decisión: **Matriz de Protocolos y Puertos Cifrados**.  
  > Implementamos una política estricta de seguridad:*
  > *- Puerto TCP 25: SMTP para la retransmisión y recepción de mensajes entre MTAs.*
  > *- Puerto TCP 587: Submission con negociación STARTTLS y autenticación obligatoria para el envío de clientes autorizados.*
  > *- Puerto TCP 993: IMAPS (IMAP sobre SSL/TLS directo), donde el cliente recupera su correo manteniendo el canal cifrado de principio a fin.*
  > 
  > *Tercera decisión: **Interoperabilidad con DNS (Registros MX y SPF)**.  
  > El servicio de correo depende estrechamente del DNS:*
  > *- El registro **MX** (Mail Exchanger) con prioridad 10 designa a `mail.empresa.local` como el nodo receptor autorizado.*
  > *- El registro **TXT (SPF)** define la directiva `"v=spf1 mx ip4:192.168.50.10 ~all"`, garantizando que únicamente nuestra IP pueda emitir correos a nombre de `empresa.local`, mitigando ataques de suplantación de identidad (*spoofing*).*
  > 
  > *Pasemos a la consola para ejecutar la implementación técnica."*

---

## C. DESARROLLO, ANÁLISIS LÍNEA POR LÍNEA Y PRUEBAS EN VIVO

### [05:00 - 06:30] Paso 1: Instalación de Paquetes de Correo y Utilidades

* **En pantalla:** Terminal de `Debian Server`.
* **Lo que dices verbalmente:**
  > *"En la consola de `Debian Server` instalamos los paquetes que integran la plataforma de mensajería ejecutando:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo apt update && sudo apt install -y postfix dovecot-imapd dovecot-pop3d mailutils swaks
  ```
* **Explicación técnica del comando y configuración inicial:**
  > *- `postfix`: Demonio MTA encargado del protocolo SMTP.*
  > *- `dovecot-imapd` y `dovecot-pop3d`: Demonios MDA para el acceso a buzones.*
  > *- `mailutils`: Binarios y comandos auxiliares del sistema para el procesamiento de correo.*
  > *- `swaks`: Utilidad especializada de auditoría y prueba de transacciones SMTP.*
  > 
  > *Durante la instalación, el asistente interactivo de Postfix despliega dos formularios:*
  > *1. En **Tipo general de configuración**, seleccionamos **Sitio de Internet** (*Internet Site*).*
  > *2. En **Nombre de correo del sistema**, escribimos `empresa.local`.*
  > 
  > *Aseguramos que el nombre quede consolidado en el archivo del sistema `/etc/mailname`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  echo "empresa.local" | sudo tee /etc/mailname
  ```
* **Explicación técnica:**
  > *- El comando `tee` escribe el dominio directamente en `/etc/mailname`, asegurando que cualquier proceso del sistema operativo identifique a `empresa.local` como el dominio de origen estándar."*

---

### [06:30 - 09:15] Paso 2: Desglose Exhaustivo Línea por Línea de Postfix (`main.cf`) y Explicación de Rutas

* **En pantalla:** Terminal de Debian Server mostrando `/etc/postfix/main.cf` con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Antes de examinar los parámetros, analicemos la lógica de las **rutas y la arquitectura de procesos de Postfix en Linux**:*
  > 
  > *- **El directorio `/etc/postfix/`:** Es el subdirectorio de `/etc` asignado a la configuración del MTA.  
  > En él encontramos dos archivos clave: `main.cf` y `master.cf` (la extensión `.cf` significa *Configuration File*).*
  > *- **Diferencia entre `main.cf` y `master.cf`:**  
  > En la filosofía Unix/Linux, Postfix no es un único programa gigante monolítico; es una familia coordinada de pequeños demonios especializados (`smtpd` para recibir conexiones, `cleanup` para sanear cabeceras, `qmgr` para administrar la cola de salida y `local` para depositar en disco).  
  > `master.cf` es el archivo supervisor que define qué demonios se ejecutan y en qué puertos escuchan (por ejemplo, el puerto 25 o el puerto 587 Submission). Por su parte, `main.cf` define las variables globales de comportamiento corporativo.*
  > 
  > *Copiamos nuestros archivos hacia `/etc/postfix/` e inspeccionamos `main.cf` con `cat -n` para auditar su estructura con numeración de líneas:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  cd ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.3/
  sudo cp main.cf /etc/postfix/main.cf
  sudo cp master.cf /etc/postfix/master.cf
  cat -n /etc/postfix/main.cf
  ```
* **Explicación técnica detallada línea por línea:**
  > *"Analicemos las directivas esenciales en pantalla:*
  > 
  > *- **Líneas 5 a 7:**
  >   - `myhostname = mail.empresa.local`: FQDN del servidor de correo.
  >   - `mydomain = empresa.local`: Dominio corporativo base.
  >   - `myorigin = /etc/mailname`: Asigna el remitente por defecto para correos generados localmente.
  > *- **Línea 21 (`mydestination`):** Declara la lista de dominios para los cuales este servidor recibe y deposita correos localmente: `$myhostname`, `empresa.local`, `localhost.empresa.local` y `localhost`.
  > *- **Línea 25 (`mynetworks`):** Define las subredes de confianza autorizadas a retransmitir tráfico a través del servidor: el rango de bucle local `127.0.0.0/8` y la subred privada `192.168.50.0/24`.
  > *- **Línea 31 (`home_mailbox = Maildir/`):** Directiva crítica. La presencia de la barra inclinada final indica a Postfix que active el formato de carpetas Maildir dentro del directorio personal del usuario (`~/Maildir/`), en lugar de utilizar un archivo Mbox monolítico.
  > 
  > *Seguridad Criptográfica TLS:*
  > *- **Líneas 43 a 46:**
  >   - `smtpd_tls_cert_file` y `smtpd_tls_key_file`: Vinculan los certificados generados con OpenSSL.
  >   - `smtpd_tls_security_level = may`: Permite negociaciones tanto oportunistas como obligatorias con cifrado TLS.
  >   - `smtpd_tls_protocols = !SSLv2, !SSLv3, !TLSv1, !TLSv1.1`: Bloquea protocolos criptográficamente inseguros.
  > 
  > *Autenticación SASL Delegada:*
  > *- **Líneas 57 a 68:**
  >   - `smtpd_sasl_type = dovecot`: Instruye a Postfix a utilizar el backend de autenticación de Dovecot.
  >   - `smtpd_sasl_path = private/auth`: Ruta del socket UNIX interno dentro del chroot de Postfix.
  >   - `smtpd_sasl_auth_enable = yes`: Habilita la autenticación para clientes remotos.
  >   - `smtpd_recipient_restrictions`: Aplica una política defensiva estricta permitiendo el paso a equipos de redes confiables (`permit_mynetworks`) o usuarios autenticados (`permit_sasl_authenticated`), y rechazando cualquier otro destino (`reject_unauth_destination`), impidiendo que el servidor sea utilizado como un 'Open Relay' por atacantes externos."*

---

### [09:15 - 11:15] Paso 3: Desglose Exhaustivo Línea por Línea de Dovecot 2.4 (`dovecot.conf`) y Sockets UNIX

* **En pantalla:** Terminal de Debian Server mostrando `/etc/dovecot/dovecot.conf` con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Procedemos a auditar la configuración de Dovecot. Aquí encontramos otro concepto de ingeniería fascinante en Linux: el directorio `/var/spool/` y los **sockets de dominio UNIX**.*
  > 
  > *- **El directorio `/var/spool/`:** En el estándar FHS, `/var/spool/` almacena colas de espera para procesos asíncronos (como colas de impresión o colas de correo en tránsito).*
  > *- **¿Qué es el socket `/var/spool/postfix/private/auth`?**  
  > Postfix opera enjaulado por seguridad en `/var/spool/postfix/`. Para que Postfix pueda autenticar usuarios sin tener que abrir un puerto de red TCP expuesto, Dovecot crea un **socket UNIX** en esa ruta. Un socket UNIX es un canal bidireccional de comunicación entre procesos (IPC) gestionado directamente en la memoria del kernel Linux, operando a la velocidad de la memoria RAM y con máxima seguridad.*
  > 
  > *En Debian 12 opera la versión **Dovecot 2.4**, la cual reemplazó la estructura modular antigua por un esquema unificado y estricto.  
  > Inspeccionamos `/etc/dovecot/dovecot.conf` con `cat -n`:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  sudo cp dovecot.conf /etc/dovecot/dovecot.conf
  cat -n /etc/dovecot/dovecot.conf
  ```
* **Explicación técnica detallada línea por línea:**
  > *- **Líneas 1 y 2:**
  >   ```text
  >   1: dovecot_config_version = 2.4.0
  >   2: dovecot_storage_version = 2.4.0
  >   ```
  >   *Estas dos líneas son de inclusión obligatoria en Dovecot 2.4. Informan al compilador de configuración la versión sintáctica exacta contra la cual validar los parámetros.*
  > *- **Línea 7 (`protocols = imap pop3`):** Inicializa los escuchas para los servicios de buzón IMAP y POP3.*
  > *- **Línea 11 (`auth_mechanisms = plain login`):** Declara los mecanismos de autenticación estándar requeridos por los clientes de correo.*
  > *- **Líneas 14 a 20 (`userdb passwd` y `passdb pam`):** Integran la validación de usuarios y contraseñas directamente contra las cuentas locales y el subsistema PAM de Linux.*
  > *- **Líneas 23 y 24:**
  >   ```text
  >   23: mail_driver = maildir
  >   24: mail_path = ~/Maildir
  >   ```
  >   *Esta es la sintaxis nativa de Dovecot 2.4 que sustituye a la directiva desaprobada `mail_location`. Indica el driver de almacenamiento y la ubicación relativa al directorio home.*
  > *- **Líneas 27 a 29:** Habilitan el cifrado SSL (`ssl = yes`) y declaran las rutas absolutas del certificado y la clave privada.*
  > *- **Líneas 32 a 38:**
  >   ```text
  >   service auth {
  >     unix_listener /var/spool/postfix/private/auth {
  >       mode = 0660
  >       user = postfix
  >       group = postfix
  >     }
  >   }
  >   ```
  >   *Crea el socket UNIX de autenticación dentro del spool de Postfix, otorgando permisos 0660 al usuario y grupo `postfix` para permitir la interoperabilidad fluida entre ambos demonios.*
  > 
  > *Validamos la sintaxis ejecutando:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo dovecot -n
  ```
* **Resultado:**
  > *- La herramienta `dovecot -n` vuelca la configuración activa sin arrojar advertencias sintácticas."*

---

### [11:15 - 12:45] Paso 4: Automatización de Cuentas y Buzones con `crear_buzones.sh`

* **En pantalla:** Terminal de Debian Server mostrando `crear_buzones.sh` con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Para realizar las pruebas de envío y recepción requerimos dos cuentas: un remitente y un destinatario. Diseñamos el script `crear_buzones.sh` para automatizar este proceso. Inspeccionemos su código con `cat -n`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.3/crear_buzones.sh
  ```
* **Explicación técnica del script:**
  > *- **Líneas 7 a 28:** Define la función `crear_usuario`:*
  >   - Verifica mediante `id "$USERNAME"` si la cuenta ya existe. Si no existe, la crea con `adduser --gecos "" --disabled-password`.*
  >   - Aplica la contraseña mediante el comando no interactivo `chpasswd`: `echo "$USERNAME:$PASSWORD" | sudo chpasswd`.*
  >   - Construye con `mkdir -p` las carpetas requeridas por la especificación Maildir: `cur`, `new` y `tmp`.*
  >   - Aplica `chmod -R 700`: Restringe los permisos para que exclusivamente el dueño tenga acceso de lectura, escritura y ejecución.*
  >   - Aplica `chown -R "$USERNAME:$USERNAME"`: Garantiza que la propiedad pertenezca a la cuenta del sistema.*
  > *- **Líneas 32 y 35:** Invoca la función creando `usuario1` y `usuario2` con la contraseña corporativa `Alumno123`.*
  > 
  > *Ejecutamos el script y reiniciamos los demonios:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  chmod +x crear_buzones.sh && sudo ./crear_buzones.sh
  sudo systemctl restart postfix dovecot
  sudo systemctl status postfix dovecot --no-pager
  ```
* **Resultado:**
  > *- Ambos servicios reportan el estado en color verde: **active (running)**."*

---

### [12:45 - 15:30] Paso 5: Verificación DNS, Envío con Swaks y Trazabilidad en Logs

* **En pantalla:** Pantalla dividida en VMware. A la izquierda la terminal del servidor monitoreando logs en vivo; a la derecha la terminal del cliente Debian XFCE.
* **Lo que dices verbalmente:**
  > *"Nos situamos en la máquina cliente Debian XFCE y auditamos la resolución de los registros DNS indispensables para el correo:*
* **Comandos a ejecutar en el Cliente Debian XFCE:**
  ```bash
  dig @192.168.50.10 empresa.local MX +short
  nslookup mail.empresa.local 192.168.50.10
  dig @192.168.50.10 empresa.local TXT +short
  ```
* **Interpretación técnica:**
  > *- El registro MX responde con prioridad 10 hacia `mail.empresa.local.`.*
  > *- El registro A confirma la dirección IP `192.168.50.10`.*
  > *- El registro TXT devuelve la política SPF válida: `"v=spf1 mx ip4:192.168.50.10 ~all"`.*
  > 
  > *Ahora, en la terminal de `Debian Server` ejecutamos el comando de seguimiento continuo de eventos de registro:*
* **Comando a ejecutar en Debian Server (Terminal Izquierda):**
  ```bash
  sudo journalctl -u postfix -u dovecot -f
  ```
* **Lo que dices verbalmente:**
  > *- `journalctl -u postfix -u dovecot -f` nos permite visualizar en tiempo real cómo los demonios procesan cada socket entrante.*
  > 
  > *En la máquina cliente (o en una segunda terminal) simulamos una transacción SMTP completa mediante la herramienta `swaks`:*
* **Comando a ejecutar:**
  ```bash
  swaks --to usuario2@empresa.local \
        --from usuario1@empresa.local \
        --server 192.168.50.10 \
        --header "Subject: Prueba de Entrega - Unidad 4 Tarea 4.3" \
        --body "Hola usuario2, este es un mensaje de prueba para verificar la entrega local en Maildir."
  ```
* **Análisis de la transacción y del log del sistema:**
  > *- La salida de `swaks` nos muestra el diálogo formal del protocolo:*
  > *1. Conexión al puerto 25 y banner `220 mail.empresa.local ESMTP Postfix`.*
  > *2. Saludo `EHLO` con negociación de capacidades.*
  > *3. `MAIL FROM:<usuario1@empresa.local>` validado con código `250 2.1.0 Ok`.*
  > *4. `RCPT TO:<usuario2@empresa.local>` validado con código `250 2.1.5 Ok`.*
  > *5. Comando `DATA`, transmisión del cuerpo y respuesta final: `250 2.0.0 Ok: queued as 3ECDAA8161`.*
  > 
  > *Observen la pantalla izquierda del servidor en los registros de `journalctl`:*
  > *La línea crucial de auditoría aparece inmediatamente:*
  > ```text
  > postfix/local[...]: to=<usuario2@empresa.local>, relay=local, status=sent (delivered to maildir)
  > ```
  > *Esta línea certifica que el agente local de Postfix entregó el correo en la estructura de almacenamiento Maildir del destinatario.*
  > 
  > *Comprobemos la existencia del archivo físico en el servidor ejecutando:*
* **Comandos a ejecutar en Debian Server:**
  ```bash
  sudo ls -la /home/usuario2/Maildir/new/
  sudo sh -c 'cat /home/usuario2/Maildir/new/*'
  ```
* **Interpretación:**
  > *- En `/home/usuario2/Maildir/new/` constatamos la creación del archivo correspondiente con sus encabezados RFC 5322 íntegros y el cuerpo legible.*
  > 
  > *Para finalizar, en el cliente Debian XFCE abrimos **Mozilla Thunderbird** configurado para `usuario2@empresa.local` con IMAP en el puerto 993 bajo SSL/TLS y SMTP en el puerto 587 con STARTTLS.*
  > *- En la **Bandeja de Entrada**, observamos el mensaje recibido sincronizado de forma instantánea por Dovecot.*
  > *- Presionamos el botón **Responder**, redactamos un mensaje de confirmación hacia `usuario1@empresa.local` y lo enviamos.*
  > *- El mensaje se transmite a través de Postfix, se almacena en la carpeta **Enviados** de Thunderbird y llega al buzón de `usuario1`, demostrando la bidireccionalidad completa del sistema de mensajería."*

---

## D. CONCLUSIONES GENERALES DE LA UNIDAD 4

### [15:30 - 17:00] Escena 6: Síntesis de la Infraestructura y Cierre Formal

* **En pantalla:** Cámara web en primer plano con el entorno de VMware de fondo.
* **Lo que dices verbalmente:**
  > *"Con esta demostración damos por concluida la Tarea 4.3 y la totalidad de los objetivos de la Unidad 4.*
  > 
  > *A lo largo de este proyecto hemos diseñado, implementado y auditado una infraestructura corporativa completa basada en Linux Debian:*
  > *1. **Infraestructura Base (Tarea 4.1):** Automatización de direccionamiento mediante **ISC-DHCP-Server** con rangos dinámicos, exclusiones de seguridad y reservas por dirección MAC; complementado con resolución autoritativa e inversa mediante **BIND9**.*
  > *2. **Servicios Web y Transferencia de Archivos (Tarea 4.2):** Publicación segura mediante el stack **LAMP (Apache, MariaDB, PHP)** con redirección **301**, cifrado **TLS con extensión SAN** y servidor **VSFTPD protegido mediante Chroot Jail**.*
  > *3. **Sistema de Mensajería Electrónica (Tarea 4.3):** Plataforma integral de correo desacoplada entre **Postfix MTA** y **Dovecot MDA**, utilizando almacenamiento no bloqueante **Maildir/**, cifrado TLS en tránsito y compatibilidad con clientes gráficos como Thunderbird.*
  > 
  > *Todas las configuraciones, scripts de automatización, manuales de pruebas y reportes técnicos con evidencias fotográficas se encuentran formalmente integrados y auditables en el repositorio público de GitHub.*
  > 
  > *Agradezco su atención a esta presentación técnica."*
