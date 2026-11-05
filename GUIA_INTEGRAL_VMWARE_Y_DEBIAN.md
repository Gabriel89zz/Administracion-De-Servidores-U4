# Guía Integral: Configuración de Red en VMware Workstation y Debian Server

Esta guía te muestra exactamente cómo configurar el entorno en tu máquina física Windows y tu máquina virtual Debian Server en VMware para que todos los servicios funcionen a la perfección y tu computadora Windows funcione como la estación cliente de pruebas sin afectar tu conexión a Internet habitual.

---

## 1. Configuración de Red en VMware Workstation (En Windows)

VMware crea adaptadores virtuales en Windows. Usaremos **VMnet1 (Host-Only)** como la red corporativa del laboratorio (`192.168.50.0/24`).

### Paso 1.1: Desactivar el DHCP interno de VMware en VMnet1
Para que sea tu servidor Debian el que reparta las direcciones IP y no VMware:
1. Abre **VMware Workstation**.
2. En la barra superior, haz clic en **Edit** > **Virtual Network Editor...** (si te pide permisos de administrador, presiona **Change Settings** en la esquina inferior).
3. Selecciona la red **VMnet1** (tipo *Host-only*).
4. En la parte inferior:
   - **DESMARCA** la casilla: `[ ] Use local DHCP service to distribute IP address to VMs`.
   - En **Subnet IP**, escribe: `192.168.50.0`
   - En **Subnet mask**, escribe: `255.255.255.0`
5. Haz clic en **Apply** y luego en **OK**.

```
+--------------------------------------------------------------+
| VMware Virtual Network Editor                                |
| VMnet1: Host-only                                            |
| [ ] Connect a host virtual adapter to this network (MARCADO) |
| [ ] Use local DHCP service... (DESMARCADO OBLIGATORIO)       |
| Subnet IP: 192.168.50.0      Subnet mask: 255.255.255.0      |
+--------------------------------------------------------------+
```

---

## 2. Configuración de Adaptadores en la Máquina Virtual Debian Server

Tu VM Debian necesita dos tarjetas de red:
- **Adaptador 1 (NAT):** Le da salida a Internet a Debian para poder descargar e instalar paquetes con `apt install`.
- **Adaptador 2 (Host-only VMnet1):** Conecta a Debian con la red corporativa `192.168.50.0/24` donde escuchará a los clientes.

### Paso 2.1: Asignar adaptadores en VMware
1. Con la máquina virtual Debian apagada o encendida, haz clic derecho sobre ella en VMware > **Settings...**
2. **Network Adapter (Adaptador 1):** Selecciona **NAT**.
3. Haz clic en **Add...** > selecciona **Network Adapter** > **Finish**.
4. En este nuevo **Network Adapter 2**: selecciona **Custom: Specific virtual network** y elige **VMnet1 (Host-only)**.
5. Haz clic en **OK**.

---

## 3. Configuración de Direccionamiento IP Estático en Debian Server

Inicia tu Debian Server y abre su consola.

### Paso 3.1: Identificar las interfaces de red
Ejecuta:
```bash
ip a
```
Verás dos interfaces además de `lo`:
- La primera (ej. `ens33` o `enp0s3`) tiene una IP del tipo `192.168.x.x` asignada por el NAT de VMware (con acceso a internet).
- La segunda (ej. `ens37` o `ens38`) es la que acabamos de agregar para VMnet1 y no tiene IP asignada aún.

### Paso 3.2: Asignar IP fija `192.168.50.10` a la segunda interfaz
Edita el archivo de interfaces:
```bash
sudo nano /etc/network/interfaces
```
Agrega al final del archivo (sustituyendo `ens37` por el nombre real de tu segunda tarjeta):

```text
# Interfaz Corporativa del Laboratorio (VMnet1)
auto ens37
iface ens37 inet static
    address 192.168.50.10
    netmask 255.255.255.0
    broadcast 192.168.50.255
```
Guarda los cambios con `Ctrl + O`, presiona Enter y sal con `Ctrl + X`.

Levanta la interfaz:
```bash
sudo ifup ens37
# O reinicia la red:
sudo systemctl restart networking
```
Verifica con `ip a` que `ens37` tenga la IP `192.168.50.10`.

---

## 4. Cómo transferir los archivos de esta carpeta hacia Debian Server

Puedes copiar fácilmente los archivos generados en este proyecto a tu Debian Server mediante **SCP** (Secure Copy) desde PowerShell en Windows:

```powershell
# En PowerShell dentro de la carpeta Unidad 4:
scp -r .\Unidad_4 tu_usuario_debian@192.168.50.10:/home/tu_usuario_debian/
```
(O usando FileZilla conectándote por SFTP en el puerto 22 con tu usuario de Debian).

---

## 5. Resumen de Flujo de Trabajo para las Prácticas

1. **Tarea 4.1 (DHCP y DNS):**
   - Copias los archivos de `Unidad_4/Tarea_4.1/` a sus rutas en `/etc/dhcp/` y `/etc/bind/`.
   - Sigues el manual `MANUAL_PRUEBAS.md`.
   - Grabas el video siguiendo `GUION_VIDEO.md` con tu webcam.
2. **Tarea 4.2 (Web HTTPS y FTP vsftpd):**
   - Ejecutas `generar_certificados.sh` en Debian.
   - Despliegas Apache y vsftpd con los archivos de `Unidad_4/Tarea_4.2/`.
   - Sigues el manual y grabas el video con `GUION_VIDEO.md`.
3. **Tarea 4.3 (Correo Postfix + Dovecot):**
   - Ejecutas `crear_buzones.sh` en Debian.
   - Copias configuraciones de Postfix y Dovecot de `Unidad_4/Tarea_4.3/`.
   - Monitoreas con `tail -f /var/log/mail.log` y grabas el video con `GUION_VIDEO.md`.

