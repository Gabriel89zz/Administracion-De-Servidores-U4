# Guion del Video Demostrativo: Tarea 4.1 (DHCP y DNS BIND9)

> **Instrucciones para la grabación:**
> - Software: **OBS Studio**.
> - **Cámara Web:** Debe estar visible y activa en la **esquina inferior derecha** de la pantalla durante todo el video.
> - **Entorno en Pantalla:** Dos ventanas de VMware en paralelo:
>   - A la izquierda: **Debian Server** (consola/terminal).
>   - A la derecha: **Debian XFCE** (estación de trabajo cliente).
> - **Duración estimada:** 4 a 6 minutos.

---

### [00:00 - 00:40] Escena 1: Presentación y Contexto del Proyecto
* **En pantalla:** Escritorio mostrando las dos máquinas virtuales en VMware y tu cámara web en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola, profesor y compañeros. Mi nombre es [Tu Nombre Completo]. En este video voy a presentar la Tarea 4.1 de la materia Administración de Servidores, correspondiente a la Unidad 4: Servicios de Infraestructura Base.*
  > 
  > *El objetivo de esta práctica es instalar, configurar y validar dos servicios esenciales en cualquier red empresarial: el servidor **DHCP**, encargado de repartir direcciones IP automáticamente, y el servidor **DNS BIND9**, que traduce los nombres de dominio a direcciones IP.*
  > 
  > *Para esta práctica contamos con dos máquinas virtuales sobre VMware: a nuestra izquierda tenemos **Debian Server** con la IP estática `192.168.50.10`, y a la derecha tenemos nuestra estación de trabajo cliente con **Debian XFCE**, ambas comunicadas mediante un segmento de red privado y totalmente aislado."*

---

### [00:40 - 01:30] Escena 2: Inspección de Archivos de Configuración en Debian Server
* **En pantalla:** Terminal de Debian Server en primer plano.
* **Acción:**
  1. Ejecutas: `cat /etc/dhcp/dhcpd.conf`
  2. Ejecutas: `cat /etc/bind/zones/db.empresa.local`
* **Lo que dices verbalmente:**
  > *"Comencemos revisando la configuración del servidor DHCP. Como pueden observar en el archivo `dhcpd.conf`, definimos nuestra red corporativa `192.168.50.0`. Implementamos un rango dinámico del `.100` al `.200`, con una exclusión del `.150` al `.160` reservada para futuros dispositivos de red. Además, configuramos una reserva estática por dirección MAC con la IP fija `192.168.50.25`.*
  > 
  > *Por el lado de DNS, en BIND9 creamos la zona directa `empresa.local` con registros tipo A para `ns1`, `www`, `mail` y `ftp`, un alias CNAME para `web`, el registro de correo MX con prioridad 10 y el registro SPF para el correo. También configuramos la zona inversa para resolver la IP hacia el nombre."*

---

### [01:30 - 02:45] Escena 3: Demostración de DHCP Dinámico y Monitoreo en Vivo
* **En pantalla:** Pantalla dividida: a la izquierda la terminal del servidor con logs en vivo; a la derecha la terminal del cliente Debian XFCE.
* **Acción en Debian Server:**
  `sudo tail -f /var/log/syslog | grep dhcpd`
* **Acción en Debian XFCE (Cliente):**
  ```bash
  sudo dhclient -r
  sudo dhclient -v
  ip a
  cat /etc/resolv.conf
  ```
* **Lo que dices verbalmente:**
  > *"Vamos a comprobar la asignación dinámica. En el servidor Debian dejamos corriendo el monitoreo de logs con `tail -f`. Ahora, en la terminal de nuestro cliente Debian XFCE, liberamos la IP y solicitamos una nueva con `dhclient -v`.*
  > 
  > *Noten en la pantalla del servidor cómo se genera en tiempo real el proceso clásico de cuatro pasos de DHCP: Discover, Offer, Request y Acknowledge.*
  > 
  > *Y si miramos el resultado en el cliente con `ip a` y `cat /etc/resolv.conf`, podemos ver que nuestro cliente recibió la IP `192.168.50.100`, la máscara `/24`, la puerta de enlace `192.168.50.1`, y lo más importante: como servidor DNS principal recibió la IP `192.168.50.10` de nuestro Debian Server."*

---

### [02:45 - 03:45] Escena 4: Demostración de la Reserva Estática por MAC
* **En pantalla:** Terminal del cliente y terminal del servidor.
* **Acción:**
  1. En el cliente muestras la MAC con: `ip link show`
  2. En el servidor muestras la coincidencia en `/etc/dhcp/dhcpd.conf` en el bloque `host cliente-vip`.
  3. En el cliente renuevas la IP:
     ```bash
     sudo dhclient -r && sudo dhclient -v
     ip a
     ```
* **Lo que dices verbalmente:**
  > *"Ahora demostraremos la reserva estática por dirección MAC. Las empresas utilizan esto para garantizar que ciertos equipos reciban siempre la misma IP sin tener que configurarla a mano.*
  > 
  > *Registramos la dirección física MAC de este cliente en el archivo `dhcpd.conf` asociada a la IP `192.168.50.25`. Al renovar la concesión, observen cómo el servidor DHCP ya no le entrega una IP al azar del grupo dinámico, sino que le entrega exactamente la IP reservada: `192.168.50.25`.*
  > *Con esto, la reserva por MAC queda completamente validada."*

---

### [03:45 - 05:00] Escena 5: Demostración de DNS BIND9 (Directa, Inversa y Reenvío)
* **En pantalla:** Terminal en Debian XFCE Cliente.
* **Acción:** Ejecutas consecutivamente:
  1. `nslookup www.empresa.local 192.168.50.10`
  2. `nslookup web.empresa.local 192.168.50.10`
  3. `dig @192.168.50.10 empresa.local MX +short`
  4. `nslookup 192.168.50.10 192.168.50.10`
  5. `nslookup google.com 192.168.50.10`
* **Lo que dices verbalmente:**
  > *"Ahora pasamos a las pruebas del servidor DNS BIND9.*
  > 
  > *Primero, resolución directa: consultamos `www.empresa.local`. Responde de inmediato indicando que la dirección asociada es `192.168.50.10`.*
  > 
  > *Segundo, alias CNAME: consultamos `web.empresa.local` y el servidor nos indica correctamente que es un nombre canónico que apunta a `www.empresa.local`.*
  > 
  > *Tercero, registro de correo MX: consultamos el registro de intercambio de correo y nos confirma que el servidor encargado es `mail.empresa.local` con prioridad 10.*
  > 
  > *Cuarto, resolución inversa: le proporcionamos la IP `192.168.50.10` y gracias a nuestro archivo de zona inversa y los registros PTR, el servidor traduce la dirección numérica de vuelta a su nombre de host `ns1.empresa.local`.*
  > 
  > *Por último, los reenviadores o forwarders: consultamos un dominio público como `google.com`. Nuestro BIND9 actúa recursivamente, consulta los servidores públicos que definimos en `named.conf.options` y nos entrega la respuesta de internet sin problemas."*

---

### [05:00 - 05:30] Escena 6: Conclusión
* **En pantalla:** Cámara web y escritorio.
* **Lo que dices verbalmente:**
  > *"Como hemos podido verificar paso a paso, tanto el servidor DHCP con rangos dinámicos, exclusiones y reservas por MAC, como el servidor DNS BIND9 con zonas directa, inversa y reenviadores están operando al 100% bajo los estándares solicitados.*
  > 
  > *Los archivos de configuración se encuentran debidamente comentados en el repositorio de GitHub y la memoria técnica detallada en el documento PDF.*
  > 
  > *Muchas gracias por su atención."*
