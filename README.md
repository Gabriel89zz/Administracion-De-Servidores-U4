# Práctica Integral de Administración de Servidores - Unidad 4

**Materia:** Administración de Servidores  
**Unidad 4:** Servidores DNS, Web, FTP, DHCP, Correos  
**Servidor:** Debian GNU/Linux 12 (Server)  
**Cliente:** Debian GNU/Linux 12 (XFCE Desktop con Firefox, FileZilla y Thunderbird)  
**Hipervisor:** VMware Workstation (Red aislada: LAN Segment `Lab-Red`)  
**Soporte Multiplataforma:** Manuales con comandos listos para clientes Linux y Windows  

---

## Estructura del Repositorio

```
.
├── GUIA_INTEGRAL_VMWARE_Y_DEBIAN.md   <-- Configuración de red en VMware (LAN Segment)
└── Unidad_4/
    ├── Tarea_4.1/                      <-- Servicios de Infraestructura Base: DHCP y DNS BIND9
    │   ├── dhcpd.conf                  (Configuración ISC-DHCP-Server con exclusiones y reserva MAC)
    │   ├── isc-dhcp-server             (Definición de interfaz de red para el demonio DHCP)
    │   ├── named.conf.options          (Forwarders a Google/Cloudflare, recursión y puertos)
    │   ├── named.conf.local            (Declaración de zonas directa e inversa)
    │   ├── db.empresa.local            (Zona directa con registros A, CNAME, MX y SPF)
    │   ├── db.192.168.50               (Zona inversa con registros PTR)
    │   ├── MANUAL_PRUEBAS.md           (Comandos duales para Linux y Windows)
    │   ├── GUION_VIDEO.md              (Guion para video en YouTube con Debian XFCE y webcam)
    │   ├── REPORTE_TECNICO.md          (Memoria técnica con guía de 6 capturas de pantalla)
    │   └── README.md
    │
    ├── Tarea_4.2/                      <-- Stack Web Seguro (HTTPS) y Transferencia FTP (vsftpd)
    │   ├── empresa.local.conf          (Virtual Host HTTP puerto 80 con redirección 301 a HTTPS)
    │   ├── empresa.local-ssl.conf      (Virtual Host HTTPS puerto 443 con SSL y TLS 1.2/1.3)
    │   ├── generar_certificados.sh     (Script de generación de certificado SSL/TLS con SAN)
    │   ├── vsftpd.conf                 (Configuración segura de vsftpd con jaula chroot)
    │   ├── index.php                   (Página web dinámica con reporte de seguridad y MariaDB)
    │   ├── db_test.php                 (Script de prueba de consulta SQL en formato JSON)
    │   ├── MANUAL_PRUEBAS.md           (Comandos duales para Linux y Windows)
    │   ├── GUION_VIDEO.md              (Guion para video en YouTube con navegador y FileZilla)
    │   ├── REPORTE_TECNICO.md          (Memoria técnica con guía de 6 capturas de pantalla)
    │   └── README.md
    │
    └── Tarea_4.3/                      <-- Servidor de Correo Electrónico (Postfix + Dovecot)
        ├── main.cf                     (Configuración principal MTA Postfix con Maildir y TLS)
        ├── master.cf                   (Servicios maestros SMTP, Submission 587 y SMTPS 465)
        ├── dovecot.conf                (Configuración raíz de Dovecot MDA)
        ├── 10-mail.conf                (Almacenamiento Maildir en directorios home)
        ├── 10-auth.conf                (Mecanismos de autenticación PLAIN y LOGIN)
        ├── 10-ssl.conf                 (Cifrado SSL/TLS para IMAP)
        ├── 10-master.conf              (Socket UNIX de autenticación Postfix-Dovecot)
        ├── crear_buzones.sh            (Script de creación de usuarios y estructura Maildir)
        ├── MANUAL_PRUEBAS.md           (Comandos duales para Linux y Windows)
        ├── GUION_VIDEO.md              (Guion para video en YouTube con Thunderbird y logs en vivo)
        ├── REPORTE_TECNICO.md          (Memoria técnica con diagrama de mensajería y capturas)
        └── README.md
```

---

## Parámetros de Red Corporativa

- **Dominio:** `empresa.local`
- **Subred Corporativa:** `192.168.50.0/24` (Máscara: `255.255.255.0`)
- **Puerta de Enlace Virtual:** `192.168.50.1`
- **Servidor Debian (IP Estática):** `192.168.50.10`
- **Rango Dinámico DHCP:** `192.168.50.100` - `192.168.50.149` y `192.168.50.161` - `192.168.50.200`
- **Exclusión DHCP:** `192.168.50.150` - `192.168.50.160`
- **Reserva Estática MAC:** `192.168.50.25`
- **Reenviadores DNS (Forwarders):** `8.8.8.8` y `1.1.1.1`
