# Reporte Técnico: Tarea 4.3 – Servidor de Correo Electrónico Seguro (Postfix MTA + Dovecot MDA)

**Materia:** Administración de Servidores  
**Unidad:** 4 – Servidores DNS, Web, FTP, DHCP, Correos  
**Alumno:** [Nombre del Alumno]  
**Docente:** [Nombre del Docente]  
**Fecha:** Noviembre 2026  
**Servidor:** Debian GNU/Linux 12 (Postfix + Dovecot + TLS)  
**Cliente:** Debian GNU/Linux 12 (XFCE Desktop con Mozilla Thunderbird) / Windows  
**Hipervisor:** VMware Workstation (LAN Segment: `Lab-Red`)  

---

## 1. Introducción y Objetivos

El sistema de mensajería electrónica es uno de los servicios más sensibles en redes empresariales. Su diseño requiere desacoplar dos responsabilidades fundamentales:
1. **Agente de Transferencia de Correo (MTA - Mail Transfer Agent):** Encargado de la transmisión, ruteo y recepción de mensajes a nivel de red mediante el protocolo **SMTP**. Para esta función se implementa **Postfix**.
2. **Agente de Entrega de Correo (MDA - Mail Delivery Agent):** Encargado del almacenamiento físico de los correos en buzones individuales y de proveer acceso seguro a los usuarios finales mediante el protocolo **IMAP**. Para esta función se despliega **Dovecot**.

El objetivo de esta práctica es desplegar la infraestructura completa de correo bajo el dominio `empresa.local`, configurando el formato de buzones `Maildir/`, cifrado TLS, autenticación SASL, registros DNS de intercambio (MX) y políticas de validación de origen (SPF), demostrando la trazabilidad del flujo de entrega a través de logs en tiempo real.

---

## 2. Diagrama de Flujo de Mensajería Electrónica

El siguiente diagrama detalla la ruta de punta a punta que sigue un correo desde que el remitente lo redacta hasta que es leído por el destinatario:

```mermaid
sequenceDiagram
    autonumber
    actor Remitente as usuario1 (Debian XFCE)
    participant Postfix as Postfix MTA (SMTP :25/:587)
    participant DNS as BIND9 DNS (MX & SPF)
    participant Dovecot as Dovecot MDA (Buzón / IMAP :993)
    actor Destinatario as usuario2 (Debian XFCE)

    Note over Remitente,Postfix: Fase 1: Envío SMTP y Verificación de Identidad
    Remitente->>Postfix: Conexión SMTP / STARTTLS
    Postfix->>Postfix: Validación SASL y políticas mynetworks
    Remitente->>Postfix: MAIL FROM: <usuario1@empresa.local>
    Remitente->>Postfix: RCPT TO: <usuario2@empresa.local>
    Postfix->>DNS: Consulta registro MX y SPF de empresa.local
    DNS-->>Postfix: MX 10 mail.empresa.local + SPF PASS
    Remitente->>Postfix: DATA (Cabeceras + Cuerpo del correo)
    Postfix-->>Remitente: 250 2.0.0 Ok: queued as [TRANSACCION_ID]

    Note over Postfix,Dovecot: Fase 2: Ruteo Local y Entrega en Disco
    Postfix->>Postfix: Limpieza de cola y resolución local
    Postfix->>Dovecot: Entrega a buzón local (/home/usuario2/Maildir/new/)
    Note right of Dovecot: Registro en /var/log/mail.log:<br/>status=sent (delivered to maildir)

    Note over Destinatario,Dovecot: Fase 3: Consulta y Recuperación IMAP
    Destinatario->>Dovecot: Conexión IMAP sobre TLS (Puerto 993)
    Destinatario->>Dovecot: LOGIN usuario2 / [Contraseña]
    Dovecot-->>Destinatario: Autenticación exitosa (200 OK)
    Destinatario->>Dovecot: SELECT INBOX & FETCH
    Dovecot-->>Destinatario: Entrega mensaje con cabeceras y contenido
```

---

## 3. Arquitectura del Sistema de Correo

### Componentes y Protocolos Implementados

| Componente | Software | Rol en la Arquitectura | Protocolo / Puerto | Seguridad / Formato |
| :--- | :--- | :--- | :--- | :--- |
| **MTA** | Postfix | Agente de Transferencia de Correo | SMTP (TCP 25) / Submission (TCP 587) | Cifrado TLS + SASL Auth Dovecot. |
| **MDA** | Dovecot | Agente de Entrega y Acceso a Buzón | IMAP (TCP 143/993) | SSL/TLS (`imaps`), formato `Maildir/`. |
| **DNS MX** | BIND9 | Intercambiador de Correo | DNS UDP 53 | Prioridad 10 -> `mail.empresa.local`. |
| **DNS SPF** | BIND9 | Validación de Origen de Correo | Registro TXT DNS | `v=spf1 mx ip4:192.168.50.10 ~all`. |
| **Buzones** | Linux FS | Almacenamiento no monolítico | Filesystem | `~/Maildir/{cur,new,tmp}` (Evita bloqueos mbox). |

---

## 4. Guía de Evidencias Fotográficas Requeridas para el PDF

> **Instrucciones para el Alumno:**  
> A continuación se detallan las **6 capturas exactas** indispensables que deben figurar en el documento de entrega:

---

### 📸 Evidencia 1: Resolución de Registros DNS de Correo (MX, A y SPF)
* **Comandos en el Cliente:**
  - **En Linux (Debian XFCE):**
    ```bash
    dig @192.168.50.10 empresa.local MX +short
    nslookup mail.empresa.local 192.168.50.10
    dig @192.168.50.10 empresa.local TXT +short
    ```
  - *(En Windows):*
    ```cmd
    nslookup -type=mx empresa.local 192.168.50.10
    nslookup mail.empresa.local 192.168.50.10
    nslookup -type=txt empresa.local 192.168.50.10
    ```
* **Qué debe verse:**
  - El registro MX devolviendo `mail.empresa.local` con preferencia 10.
  - El registro A de `mail.empresa.local` apuntando a `192.168.50.10`.
  - El registro TXT mostrando la directiva SPF autorizando a la IP del servidor.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 1: Registros DNS MX, A y SPF para el servicio de correo]`  
  > *Figura 3.1: Verificación de los registros DNS indispensables para la interoperabilidad de correo.*

---

### 📸 Evidencia 2: Estado Activo y Carga de Servicios Postfix y Dovecot
* **Comandos en Debian Server:**
  ```bash
  sudo systemctl status postfix --no-pager
  sudo systemctl status dovecot --no-pager
  ```
* **Qué debe verse:** Ambos servicios en color verde mostrando el estado `active (running)`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 2: Estado de ejecución de los demonios Postfix y Dovecot]`  
  > *Figura 3.2: Confirmación de operatividad de los agentes MTA y MDA en Debian Server.*

---

### 📸 Evidencia 3: Transacción SMTP de Envío de Correo (Swaks / Thunderbird)
* **Acción en el Cliente:**
  - Si usas `swaks`:
    ```bash
    swaks --to usuario2@empresa.local --from usuario1@empresa.local --server 192.168.50.10 --header "Subject: Prueba de Entrega - Unidad 4" --body "Comprobando la entrega exitosa del correo electronico."
    ```
  - Si usas Thunderbird en Debian XFCE: redactar el correo desde `usuario1` hacia `usuario2` con el asunto indicado y presionar **Enviar**.
* **Qué debe verse:** La salida del comando mostrando el apretón de manos SMTP, la cabecera `MAIL FROM`, `RCPT TO` y la respuesta final del servidor `250 2.0.0 Ok: queued as ...`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 3: Diálogo SMTP de envío de correo exitoso]`  
  > *Figura 3.3: Negociación SMTP y aceptación del mensaje en la cola de Postfix.*

---

### 📸 Evidencia 4: Trazabilidad en Tiempo Real en `/var/log/mail.log` (Evidencia Clave)
* **Comando en Debian Server:**
  ```bash
  sudo tail -n 20 /var/log/mail.log
  ```
  *(O `sudo journalctl -u postfix -n 20`)*
* **Qué debe verse:** La secuencia completa de logs:
  - `postfix/smtpd: connect`
  - `postfix/cleanup: message-id`
  - `postfix/qmgr: from=<usuario1@empresa.local>`
  - **`postfix/local: to=<usuario2@empresa.local>, status=sent (delivered to maildir)`** *(Resaltar este renglón)*
  - `postfix/qmgr: removed`
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 4: Trazabilidad del flujo de entrega en /var/log/mail.log]`  
  > *Figura 3.4: Registro del sistema certificando la entrega efectiva en el buzón Maildir del destinatario.*

---

### 📸 Evidencia 5: Recepción del Correo en el Buzón Físico (`Maildir/new`)
* **Comando en Debian Server:**
  ```bash
  ls -lh /home/usuario2/Maildir/new/
  cat /home/usuario2/Maildir/new/*
  ```
* **Qué debe verse:** El archivo físico generado dentro de `new/`, con el asunto, remitente, fecha y cuerpo del mensaje legible.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 5: Inspección del mensaje en el sistema de archivos del usuario]`  
  > *Figura 3.5: Almacenamiento del mensaje en formato individual Maildir en el directorio del destinatario.*

---

### 📸 Evidencia 6: Visualización del Correo en Cliente MUA (Mozilla Thunderbird en Debian XFCE)
* **Acción en el Cliente Debian XFCE:**
  - Abrir Mozilla Thunderbird con la cuenta de `usuario2@empresa.local`.
  - Mostrar la **Bandeja de entrada** con el correo recibido.
  - Abrir el correo mostrando las cabeceras completas y el botón **Responder**.
* **Qué debe verse:** La interfaz de Thunderbird en Debian XFCE con la cuenta sincronizada por IMAP sobre TLS y el mensaje de prueba desplegado.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 6: Visualización del correo en la bandeja de entrada de Thunderbird]`  
  > *Figura 3.6: Recuperación del mensaje mediante el protocolo IMAP bajo TLS en cliente gráfico.*

---

## 5. Conclusiones Técnicas

1. **Ventajas del Formato Maildir frente a Mbox:** La elección de `home_mailbox = Maildir/` previene condiciones de carrera y bloqueos de archivos en el sistema operativo (*file-locking*), ya que cada correo es un archivo independiente con identificador único.
2. **Importancia de los Registros SPF y MX:** Un servidor de correo no puede operar de forma aislada sin una adecuada integración con el DNS. El registro MX encamina el tráfico hacia el host receptor, mientras que las políticas SPF mitigan la suplantación de identidad (*spoofing*).
3. **Seguridad Criptográfica en la Entrega:** La combinación de Postfix con cifrado STARTTLS y Dovecot con SSL/TLS en IMAP asegura que las contraseñas y contenidos nunca viajen en texto claro por la red.

---

## 6. Enlaces de Entrega

* **Repositorio Público de GitHub:** `https://github.com/[Tu_Usuario]/[Tu_Repositorio]/tree/main/Unidad_4/Tarea_4.3`
* **Video Demostrativo en YouTube:** `https://youtu.be/[ID_DE_TU_VIDEO]`
