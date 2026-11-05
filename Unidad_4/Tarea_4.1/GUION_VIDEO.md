# Guion del Video Demostrativo: Tarea 4.1 (DHCP y DNS BIND9)

> **Instrucciones para la grabación:**
> - Utiliza **OBS Studio** o la barra de juegos de Windows.
> - **Cámara Web:** Debe estar visible y activa en la **esquina inferior derecha** de la pantalla durante todo el video.
> - **Resolución:** Graba a 1080p para que el texto de las terminales se lea nítido.
> - **Duración estimada:** 4 a 6 minutos.

---

### [00:00 - 00:40] Escena 1: Presentación y Contexto del Proyecto
* **En pantalla:** Escritorio mostrando las ventanas de VMware (Debian Server) y PowerShell en Windows. Cámara web en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola, profesor y compañeros. Mi nombre es [Tu Nombre Completo]. En este video voy a presentar la Tarea 4.1 de la materia Administración de Servidores, correspondiente a la Unidad 4: Servicios de Infraestructura Base.*
  > 
  > *El objetivo de esta práctica es instalar, configurar y validar dos servicios esenciales en cualquier red empresarial: el servidor **DHCP**, encargado de repartir direcciones IP automáticamente, y el servidor **DNS BIND9**, que traduce los nombres de dominio a direcciones IP legibles para las computadoras.*
  > 
  > *Para esta práctica contamos con un servidor virtual con **Debian GNU/Linux** en VMware, configurado con la IP estática `192.168.50.10`, y utilizaremos como estación cliente el adaptador de red virtual de nuestro equipo anfitrión Windows, trabajando en un entorno de red totalmente aislado."*

---

### [00:40 - 01:30] Escena 2: Inspección de Archivos de Configuración en Debian
* **En pantalla:** Terminal de Debian Server en primer plano.
* **Acción:**
  1. Ejecutas: `cat /etc/dhcp/dhcpd.conf`
  2. Ejecutas: `cat /etc/bind/zones/db.empresa.local`
* **Lo que dices verbalmente:**
  > *"Comencemos revisando la configuración del servidor DHCP. Como pueden observar en el archivo `dhcpd.conf`, hemos definido nuestra red corporativa `192.168.50.0`. Implementamos un rango dinámico del `.100` al `.200`, con una exclusión del `.150` al `.160` para futuras impresoras o servidores de red. Además, configuramos una reserva estática por dirección MAC con la IP fija `192.168.50.25`.*
  > 
  > *Por el lado de DNS, en BIND9 creamos la zona directa `empresa.local` con registros tipo A para `ns1`, `www`, `mail` y `ftp`, un alias CNAME para `web`, el registro de correo MX con prioridad 10, y el registro TXT para las políticas SPF. También configuramos la zona inversa para resolver la IP hacia el nombre."*

---

### [01:30 - 02:45] Escena 3: Demostración de DHCP (Dinámico y Monitoreo en Vivo)
* **En pantalla:** Dividir la pantalla en dos: a la izquierda la terminal de Debian con el log en vivo, a la derecha PowerShell en Windows.
* **Acción en Debian:** Ejecutas `sudo tail -f /var/log/syslog | grep dhcpd`
* **Acción en Windows:** Ejecutas:
  ```powershell
  ipconfig /release "VMware Network Adapter VMnet1"
  ipconfig /renew "VMware Network Adapter VMnet1"
  ipconfig /all
  ```
* **Lo que dices verbalmente:**
  > *"Vamos a comprobar la asignación dinámica. En el servidor Debian dejamos corriendo el monitoreo de logs con `tail -f`. Ahora, desde la consola de Windows, solicitamos la liberación y renovación de la IP mediante `ipconfig /renew`.*
  > 
  > *Noten en la pantalla de Debian cómo se genera en tiempo real el proceso de cuatro pasos de DHCP: Discover, Offer, Request y el Acknowledge final.*
  > 
  > *Y si miramos el resultado en Windows con `ipconfig /all`, podemos ver que nuestro adaptador recibió la IP `192.168.50.100`, la puerta de enlace `192.168.50.1`, y lo más importante: nos entregó como servidor DNS principal la IP `192.168.50.10` de nuestro Debian Server."*

---

### [02:45 - 03:45] Escena 4: Demostración de la Reserva Estática por MAC
* **En pantalla:** PowerShell en Windows y terminal de Debian.
* **Acción:**
  1. En Windows muestras la MAC: `Get-NetAdapter -Name "*VMnet1*"`
  2. En Debian muestras la coincidencia en `/etc/dhcp/dhcpd.conf` en el bloque `host cliente-vip`.
  3. En Windows renuevas la IP:
     ```powershell
     ipconfig /release "VMware Network Adapter VMnet1"
     ipconfig /renew "VMware Network Adapter VMnet1"
     ```
* **Lo que dices verbalmente:**
  > *"Ahora demostraremos la reserva estática por dirección MAC. Las empresas utilizan esto para garantizar que directores, servidores o impresoras reciban siempre la misma IP sin tener que configurarla manualmente.*
  > 
  > *Registramos la dirección física de esta tarjeta de red en el archivo `dhcpd.conf` vinculada a la IP `192.168.50.25`. Al renovar la concesión, observen cómo el servidor DHCP ya no le da una IP al azar del rango dinámico, sino que le entrega exactamente la IP `192.168.50.25`.*
  > *Con esto, la reserva por MAC queda completamente validada."*

---

### [03:45 - 05:00] Escena 5: Demostración de DNS BIND9 (Directa, Inversa y Reenvío)
* **En pantalla:** Consola de comandos en Windows.
* **Acción:** Ejecutas consecutivamente los siguientes comandos:
  1. `nslookup www.empresa.local 192.168.50.10`
  2. `nslookup web.empresa.local 192.168.50.10`
  3. `nslookup -type=mx empresa.local 192.168.50.10`
  4. `nslookup 192.168.50.10 192.168.50.10`
  5. `nslookup google.com 192.168.50.10`
* **Lo que dices verbalmente:**
  > *"Ahora pasamos a las pruebas del servidor DNS BIND9.*
  > 
  > *Primero, resolución directa: consultamos `www.empresa.local`. Como vemos, responde de inmediato indicando que la dirección asociada es `192.168.50.10`.*
  > 
  > *Segundo, alias CNAME: consultamos `web.empresa.local` y el servidor nos indica correctamente que es un nombre canónico que apunta a `www.empresa.local`.*
  > 
  > *Tercero, registro de correo MX: consultamos el registro de intercambio de correo y nos confirma que el servidor encargado de recibir los mensajes es `mail.empresa.local` con prioridad 10.*
  > 
  > *Cuarto, resolución inversa: le proporcionamos la IP `192.168.50.10` y gracias a nuestro archivo de zona inversa y los registros PTR, el servidor traduce la dirección numérica de vuelta a su nombre de host `ns1.empresa.local`.*
  > 
  > *Por último, los reenviadores o forwarders: consultamos un dominio público de internet como `google.com`. Nuestro BIND9 actúa recursivamente, consulta los servidores de Google y Cloudflare que definimos en `named.conf.options` y nos entrega la respuesta pública sin problemas."*

---

### [05:00 - 05:30] Escena 6: Conclusión
* **En pantalla:** Cámara web y escritorio.
* **Lo que dices verbalmente:**
  > *"Como hemos podido verificar paso a paso, tanto el servidor DHCP con rangos dinámicos, exclusiones y reservas por MAC, como el servidor DNS BIND9 con zonas directa, inversa y reenviadores están operando al 100% bajo los estándares solicitados.*
  > 
  > *Los archivos de configuración se encuentran debidamente comentados en el repositorio de GitHub y la memoria técnica detallada en el documento PDF.*
  > 
  > *Muchas gracias por su atención."*

