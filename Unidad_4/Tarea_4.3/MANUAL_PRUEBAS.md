# Manual de Pruebas: Tarea 4.3 (Servidor de Correo Postfix + Dovecot)

Este manual contiene las instrucciones detalladas con los comandos y configuraciones exactas para instalar, configurar y verificar el servidor de correo corporativo utilizando **Postfix (MTA)** y **Dovecot (MDA)** con soporte para protocolos **SMTP e IMAP bajo TLS**, probado desde Debian Server y la máquina Windows (con Thunderbird o terminal).

---

## 1. Despliegue en el Servidor Debian

### Paso 1.1: Instalación de Paquetes
Ejecuta en Debian:
```bash
sudo apt update
sudo apt install -y postfix dovecot-imapd dovecot-pop3d mailutils swaks
```
> *Durante la instalación de Postfix, si aparece el asistente en pantalla:*
> - Selecciona **Sitio de Internet** (*Internet Site*).
> - En **Nombre de correo del sistema** (*System mail name*), escribe: `empresa.local`.

### Paso 1.2: Configuración del Nombre de Correo
Asegúrate de que `/etc/mailname` contenga el dominio:
```bash
echo "empresa.local" | sudo tee /etc/mailname
```

### Paso 1.3: Copiar Configuraciones de Postfix y Dovecot
```bash
# Copiar configuración de Postfix
sudo cp main.cf /etc/postfix/main.cf
sudo cp master.cf /etc/postfix/master.cf

# Copiar configuración de Dovecot
sudo cp dovecot.conf /etc/dovecot/dovecot.conf
sudo cp 10-mail.conf /etc/dovecot/conf.d/10-mail.conf
sudo cp 10-auth.conf /etc/dovecot/conf.d/10-auth.conf
sudo cp 10-ssl.conf /etc/dovecot/conf.d/10-ssl.conf

# Validar permisos de socket de autenticación en /etc/dovecot/conf.d/10-master.conf
# Asegúrate de descomentar o agregar la sección para el socket de Postfix:
sudo sed -i '/service auth {/,/}/ s/#unix_listener \/var\/spool\/postfix\/private\/auth/unix_listener \/var\/spool\/postfix\/private\/auth/' /etc/dovecot/conf.d/10-master.conf
```

### Paso 1.4: Crear Usuarios y Buzones Maildir
Ejecuta el script incluido:
```bash
chmod +x crear_buzones.sh
sudo ./crear_buzones.sh
```
> Esto creará `usuario1` y `usuario2` con sus respectivos directorios `~/Maildir/{cur,new,tmp}` y contraseña `Alumno123!`.

### Paso 1.5: Reiniciar Servicios y Validar Estado
```bash
# Reiniciar Postfix y Dovecot
sudo systemctl restart postfix
sudo systemctl restart dovecot

# Comprobar que estén activos (running)
sudo systemctl status postfix --no-pager
sudo systemctl status dovecot --no-pager
```

---

## 2. Verificación de Registros DNS para Correo

Desde Windows (PowerShell/CMD) o Debian, verifica que el DNS reconozca el servidor de correo y las políticas SPF:

```cmd
# Comprobar registro MX
nslookup -type=mx empresa.local 192.168.50.10

# Comprobar registro A del servidor de correo
nslookup mail.empresa.local 192.168.50.10

# Comprobar registro SPF (TXT)
nslookup -type=txt empresa.local 192.168.50.10
```
> **Resultado esperado:**
> - MX: `preference = 10, mail exchanger = mail.empresa.local`
> - A: `192.168.50.10`
> - SPF: `"v=spf1 mx ip4:192.168.50.10 ~all"`

---

## 3. Pruebas de Envío y Trazabilidad en Tiempo Real (Log)

Esta es la prueba reina exigida para el video y el reporte.

### Paso 3.1: Monitoreo en Vivo de Logs en Debian
Abre una terminal en Debian Server y déjala abierta con este comando:
```bash
sudo tail -f /var/log/mail.log
```
*(En Debian 12 o superior, si el log se gestiona por systemd, puedes usar: `sudo journalctl -u postfix -u dovecot -f`)*

### Paso 3.2: Envío de Correo de Prueba con `swaks`
En otra terminal de Debian (o desde el cliente), ejecuta el comando `swaks` para simular una transacción SMTP completa entre `usuario1` y `usuario2`:
```bash
swaks --to usuario2@empresa.local \
      --from usuario1@empresa.local \
      --server 192.168.50.10 \
      --header "Subject: Prueba de Entrega - Unidad 4 Tarea 4.3" \
      --body "Hola usuario2, este es un mensaje de prueba para verificar la entrega local en Maildir."
```

> **Verificación en el Log de Postfix (`/var/log/mail.log`):**
> Verás aparecer en vivo las siguientes líneas:
> 1. `postfix/smtpd[...]: connect from localhost[127.0.0.1]`
> 2. `postfix/smtpd[...]: <ID_TRANSACCION>: client=localhost[127.0.0.1]`
> 3. `postfix/cleanup[...]: <ID_TRANSACCION>: message-id=...`
> 4. `postfix/qmgr[...]: <ID_TRANSACCION>: from=<usuario1@empresa.local>, size=..., nrcpt=1 (queue active)`
> 5. `postfix/local[...]: <ID_TRANSACCION>: to=<usuario2@empresa.local>, relay=local, delay=..., status=sent (delivered to maildir)`
> 6. `postfix/qmgr[...]: <ID_TRANSACCION>: removed`
> 
> **¡El mensaje `status=sent (delivered to maildir)` confirma la entrega exitosa!**

---

## 4. Verificación en la Bandeja del Destinatario (Buzón Maildir)

Comprueba directamente en el sistema de archivos del usuario destinatario:
```bash
ls -la /home/usuario2/Maildir/new/
```
> **Resultado esperado:** Aparece un archivo con el correo recibido. Puedes leer su contenido con:
```bash
cat /home/usuario2/Maildir/new/*
```
Observarás todas las cabeceras SMTP (`From:`, `To:`, `Subject:`, `Date:`) y el cuerpo del mensaje.

---

## 5. Pruebas con Cliente MUA Gráfico (Mozilla Thunderbird en Windows)

Si deseas demostrarlo con interfaz gráfica en tu Windows:

1. Abre **Mozilla Thunderbird** en Windows.
2. Agrega una nueva cuenta de correo:
   - **Nombre:** Usuario 2
   - **Dirección de correo:** `usuario2@empresa.local`
   - **Contraseña:** `Alumno123!`
3. Haz clic en **Configurar manualmente**:
   - **Servidor de entrada (IMAP):**
     - Nombre de host: `mail.empresa.local` (o `192.168.50.10`)
     - Puerto: `143` (STARTTLS) o `993` (SSL/TLS)
     - Autenticación: Contraseña normal
     - Nombre de usuario: `usuario2`
   - **Servidor de salida (SMTP):**
     - Nombre de host: `mail.empresa.local` (o `192.168.50.10`)
     - Puerto: `587` (STARTTLS) o `25` (Ninguna)
     - Autenticación: Contraseña normal
     - Nombre de usuario: `usuario2`
4. Acepta el certificado autofirmado cuando aparezca la advertencia.
5. Haz clic en **Bandeja de entrada**: ¡Aparecerá el correo de prueba enviado por `usuario1`!
6. Haz clic en **Responder**, escribe un mensaje de vuelta para `usuario1@empresa.local` y presiona **Enviar**.
7. En el log del servidor se registrará la nueva transacción y el correo llegará a `/home/usuario1/Maildir/new/`.

