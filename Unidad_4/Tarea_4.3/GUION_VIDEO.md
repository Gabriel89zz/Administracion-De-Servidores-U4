# Guion del Video Demostrativo: Tarea 4.3 (Servidor de Correo Postfix + Dovecot)

> **Instrucciones para la grabación:**
> - Software: **OBS Studio**.
> - **Cámara Web:** Activa y visible en la **esquina inferior derecha** de la pantalla en todo momento.
> - **Duración estimada:** 4 a 6 minutos.
> - **Tono:** Didáctico, claro y profesional.

---

### [00:00 - 00:40] Escena 1: Presentación de la Tarea y Arquitectura
* **En pantalla:** Escritorio con la terminal de Debian y cliente Thunderbird o terminal secundaria. Cámara web en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola, bienvenidos. Mi nombre es [Tu Nombre Completo]. En este video presentaremos la Tarea 4.3 de la materia Administración de Servidores, enfocada en la implementación y aseguramiento de un **Servidor de Correo Electrónico Corporativo**.*
  > 
  > *Para entender cómo funciona el correo electrónico, debemos saber que intervienen dos componentes principales:*
  > *1. El **MTA** o Agente de Transferencia de Correo, para el cual utilizamos **Postfix**. Este servicio se encarga de enviar y enrutar los correos a través del protocolo **SMTP**.*
  > *2. El **MDA** o Agente de Entrega de Correo, para el cual usamos **Dovecot**. Este se encarga de depositar los mensajes en los buzones de los usuarios y permitirles recuperarlos mediante el protocolo **IMAP**.*
  > 
  > *Todo esto está respaldado por los registros DNS MX y SPF que configuramos previamente en BIND9."*

---

### [00:40 - 01:25] Escena 2: Verificación de Registros DNS para Correo (MX y SPF)
* **En pantalla:** Consola en Debian o Windows.
* **Acción:**
  1. Ejecutas: `nslookup -type=mx empresa.local 192.168.50.10`
  2. Ejecutas: `nslookup -type=txt empresa.local 192.168.50.10`
* **Lo que dices verbalmente:**
  > *"Antes de enviar cualquier correo, demostraremos que los cimientos de red están listos.*
  > 
  > *Al consultar el registro **MX**, vemos que nuestro servidor DNS indica con prioridad 10 que el encargado de recibir los mensajes es `mail.empresa.local`.*
  > 
  > *Y al consultar el registro **TXT**, observamos la política **SPF**. El registro SPF le dice al mundo qué servidores tienen permiso de enviar correos a nombre de nuestro dominio, evitando así la falsificación de identidad o spam."*

---

### [01:25 - 03:30] Escena 3: Envío del Correo y Monitoreo en Tiempo Real (Trazabilidad)
* **En pantalla:** Pantalla dividida: a la izquierda la terminal del servidor con el log en vivo a pantalla grande; a la derecha la ventana donde enviaremos el correo (Thunderbird o terminal con `swaks`).
* **Acción en Debian:** Ejecutas:
  `sudo tail -f /var/log/mail.log`
* **Acción de Envío:**
  - Si usas terminal:
    ```bash
    swaks --to usuario2@empresa.local --from usuario1@empresa.local --server 192.168.50.10 --header "Subject: Prueba de Entrega - Unidad 4" --body "Comprobando la entrega exitosa del correo electronico."
    ```
  - Si usas Thunderbird: redactas el mensaje desde `usuario1` hacia `usuario2` y presionas **Enviar**.
* **Lo que dices verbalmente:**
  > *"Ahora pasamos a la prueba de trazabilidad en tiempo real. En la terminal del servidor Debian dejamos corriendo el monitoreo del archivo de registro con `tail -f /var/log/mail.log`.*
  > 
  > *Desde nuestra estación cliente redactamos un correo desde la cuenta `usuario1@empresa.local` dirigido a `usuario2@empresa.local` con el asunto 'Prueba de Entrega'.*
  > 
  > *Presionamos Enviar. ¡Miren atentamente la consola de Debian!*
  > *Observen la secuencia exacta de eventos:*
  > *1. Primero, Postfix recibe la conexión entrante por el puerto SMTP.*
  > *2. Valida al remitente y al destinatario.*
  > *3. Asigna un identificador único a la transacción.*
  > *4. Y aquí vemos la línea más importante: `postfix/local: status=sent (delivered to maildir)`.*
  > 
  > *Esto demuestra de manera irrefutable que el mensaje fue aceptado, procesado y entregado exitosamente en el buzón local del destinatario."*

---

### [03:30 - 04:30] Escena 4: Verificación en la Bandeja del Destinatario
* **En pantalla:** Bandeja de entrada de Thunderbird o la terminal mostrando el directorio `/home/usuario2/Maildir/new/`.
* **Acción:**
  1. Si es en Thunderbird: seleccionas la bandeja de entrada de `usuario2` y abres el mensaje recibido.
  2. Si es por consola: ejecutas `cat /home/usuario2/Maildir/new/*`
  3. Muestras las cabeceras: `Return-Path`, `From: usuario1@empresa.local`, `To: usuario2@empresa.local`, la fecha y el cuerpo del mensaje.
  4. (Opcional recomendado): Presionas **Responder** para enviar una respuesta de vuelta hacia `usuario1`, mostrando brevemente en el log que el flujo es completamente bidireccional.
* **Lo que dices verbalmente:**
  > *"Ahora nos posicionamos en el cliente como el destinatario: `usuario2`.*
  > 
  > *Como pueden observar en la bandeja de entrada (o en la carpeta `Maildir/new`), aquí ha llegado el correo íntegro.*
  > *Al abrirlo, podemos verificar todas las cabeceras del protocolo SMTP: el remitente, el destinatario, la marca de tiempo exacta y el cuerpo del mensaje.*
  > 
  > *Respondemos al mensaje para comprobar que la comunicación fluye en ambos sentidos de manera fluida y segura."*

---

### [04:30 - 05:00] Escena 5: Conclusión General de la Unidad 4
* **En pantalla:** Cámara web y escritorio.
* **Lo que dices verbalmente:**
  > *"Con esto concluimos la demostración de la Tarea 4.3 y de toda la Unidad 4.*
  > *Hemos implementado y validado con éxito:*
  > *- Servicios de infraestructura base: DHCP dinámico y con reservas por MAC, y DNS BIND9 con zonas directa, inversa y reenviadores.*
  > *- Servicios Web HTTPS y transferencia FTP securizada con jaula Chroot.*
  > *- Servidor de correo corporativo con Postfix, Dovecot, registros MX y SPF.*
  > 
  > *Todos los archivos de configuración, scripts y memorias técnicas se encuentran listos y documentados en el repositorio de GitHub.*
  > *Muchas gracias por su atención."*

