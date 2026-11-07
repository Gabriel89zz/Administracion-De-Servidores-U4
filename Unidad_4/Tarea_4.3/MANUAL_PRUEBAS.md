# Manual de Pruebas: Tarea 4.3 (Servidor de Correo Postfix + Dovecot)

Este manual contiene las instrucciones detalladas con los comandos y configuraciones exactas para instalar, configurar y verificar el servidor de correo corporativo utilizando **Postfix (MTA)** y **Dovecot (MDA)** con soporte para protocolos **SMTP e IMAP bajo TLS**, probado desde Debian Server y la máquina cliente con **Debian XFCE** (o Windows).

---

## 1. Despliegue en el Servidor Debian

### Paso 1.1: Instalación de Paquetes
Ejecuta en Debian Server:
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
Ubícate en la carpeta de la tarea dentro del repositorio:
```bash
cd ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.3/

# Copiar configuración de Postfix
sudo cp main.cf /etc/postfix/main.cf
sudo cp master.cf /etc/postfix/master.cf

# Copiar configuración unificada nativa de Dovecot 2.4
sudo cp dovecot.conf /etc/dovecot/dovecot.conf
```
```

### Paso 1.4: Crear Usuarios y Buzones Maildir
Ejecuta el script incluido:
```bash
chmod +x crear_buzones.sh
sudo ./crear_buzones.sh
```
> Esto creará `usuario1` y `usuario2` con sus respectivos directorios `~/Maildir/{cur,new,tmp}` y contraseña `Alumno123`.

### Paso 1.5: Reiniciar Servicios y Validar Estado
```bash
sudo systemctl restart postfix
sudo systemctl restart dovecot
sudo systemctl status postfix --no-pager
sudo systemctl status dovecot --no-pager
```

---

## 2. Verificación de Registros DNS para Correo

Desde el cliente (Debian XFCE o Windows):

* **En Linux (Debian XFCE):**
  ```bash
  # Comprobar registro MX
  dig @192.168.50.10 empresa.local MX +short
  # Comprobar registro A del servidor de correo
  nslookup mail.empresa.local 192.168.50.10
  # Comprobar registro SPF (TXT)
  dig @192.168.50.10 empresa.local TXT +short
  ```

* **En Windows:**
  ```cmd
  nslookup -type=mx empresa.local 192.168.50.10
  nslookup mail.empresa.local 192.168.50.10
  nslookup -type=txt empresa.local 192.168.50.10
  ```
> **Resultado esperado:**
> - MX: `10 mail.empresa.local.`
> - A: `192.168.50.10`
> - SPF: `"v=spf1 mx ip4:192.168.50.10 ~all"`

---

## 3. Pruebas de Envío y Trazabilidad en Tiempo Real (Log)

### Paso 3.1: Monitoreo en Vivo de Logs en Debian Server
Abre una terminal en Debian Server y ejecuta el comando de monitoreo:
```bash
# Opción nativa en Debian 12 (Recomendada):
sudo journalctl -u postfix -u dovecot -f

# Opción tradicional (si tienes rsyslog activo):
# sudo tail -f /var/log/mail.log
```

### Paso 3.2: Envío de Correo de Prueba

#### Opción A: Mediante `swaks` desde la terminal del Cliente o Servidor
```bash
swaks --to usuario2@empresa.local \
      --from usuario1@empresa.local \
      --server 192.168.50.10 \
      --header "Subject: Prueba de Entrega - Unidad 4 Tarea 4.3" \
      --body "Hola usuario2, este es un mensaje de prueba para verificar la entrega local en Maildir."
```

#### Opción B: Mediante Mozilla Thunderbird en Debian XFCE (Cliente)
Configura la cuenta de `usuario1` o `usuario2`, redacta el mensaje y haz clic en **Enviar**.

> **Verificación en el Log de Postfix (`/var/log/mail.log`):**
> Verás aparecer en vivo las siguientes líneas:
> 1. `postfix/smtpd[...]: connect from ...`
> 2. `postfix/smtpd[...]: <ID_TRANSACCION>: client=...`
> 3. `postfix/cleanup[...]: <ID_TRANSACCION>: message-id=...`
> 4. `postfix/qmgr[...]: <ID_TRANSACCION>: from=<usuario1@empresa.local>, size=..., nrcpt=1 (queue active)`
> 5. **`postfix/local[...]: <ID_TRANSACCION>: to=<usuario2@empresa.local>, relay=local, delay=..., status=sent (delivered to maildir)`**
> 6. `postfix/qmgr[...]: <ID_TRANSACCION>: removed`
> 
> **¡El mensaje `status=sent (delivered to maildir)` certifica la entrega exitosa!**

---

## 4. Verificación en la Bandeja del Destinatario (Buzón Maildir)

Comprueba en Debian Server el archivo físico del correo en el directorio del usuario:
```bash
ls -la /home/usuario2/Maildir/new/
cat /home/usuario2/Maildir/new/*
```
Observarás todas las cabeceras SMTP (`From:`, `To:`, `Subject:`, `Date:`) y el cuerpo del mensaje.

---

## 5. Pruebas con Cliente MUA Gráfico (Mozilla Thunderbird en Debian XFCE)

1. En **Debian XFCE**, abre **Mozilla Thunderbird** desde el menú de aplicaciones.
2. Agrega una cuenta de correo:
   - **Nombre:** Usuario 2
   - **Dirección de correo:** `usuario2@empresa.local`
   - **Contraseña:** `Alumno123!`
3. Haz clic en **Configurar manualmente**:
   - **Servidor de entrada (IMAP):**
     - Host: `mail.empresa.local` (o `192.168.50.10`)
     - Puerto: `143` (STARTTLS) o `993` (SSL/TLS)
     - Autenticación: Contraseña normal
     - Nombre de usuario: `usuario2`
   - **Servidor de salida (SMTP):**
     - Host: `mail.empresa.local` (o `192.168.50.10`)
     - Puerto: `587` (STARTTLS) o `25` (Ninguna)
     - Autenticación: Contraseña normal
     - Nombre de usuario: `usuario2`
4. Acepta el certificado autofirmado cuando aparezca la advertencia.
5. Haz clic en **Bandeja de entrada**: ¡Aparecerá el correo de prueba enviado por `usuario1`!
6. Haz clic en **Responder**, escribe un mensaje de vuelta para `usuario1@empresa.local` y presiona **Enviar**.
7. En el log del servidor se registrará la nueva transacción y el correo llegará a `/home/usuario1/Maildir/new/`.
