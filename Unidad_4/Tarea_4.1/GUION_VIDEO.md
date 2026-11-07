# Guion Maestro del Video Tutorial: Tarea 4.1 (Servidor DHCP e Infraestructura DNS BIND9)

> **Perfil del Presentador:** Ingeniero Senior en Sistemas y Administrador de Infraestructuras Linux.  
> **Formato de Grabación:** OBS Studio con captura de pantalla y **cámara web activa en la esquina inferior derecha** en todo momento.  
> **Disposición de Pantalla:** Dos máquinas virtuales en VMware Workstation:
> - **Lado Izquierdo:** `Debian Server` (Debian 12 sin entorno gráfico / Consola SSH). IP estática: `192.168.50.10`.
> - **Lado Derecho:** `Debian XFCE Cliente` (Debian 12 con interfaz gráfica XFCE). MAC: `00:0c:29:e6:8a:3a`.
> **Segmento de Red:** Privado y aislado mediante VMware LAN Segment `Lab-Red` (`192.168.50.0/24`).  
> **Duración aproximada:** 10 a 15 minutos (Tutorial formativo completo).

---

## ESTRUCTURA DEL TUTORIAL

```
├── A. INTRODUCCIÓN Y REQUISITOS (00:00 - 02:00)
│   ├── Presentación y planteamiento del problema
│   ├── Objetivos didácticos y arquitectura del laboratorio
│   └── Por qué utilizamos VMware LAN Segment
│
├── B. FUNDAMENTOS TÉCNICOS ESENCIALES (02:00 - 04:00)
│   ├── Protocolo DHCP y el ciclo de vida DORA
│   └── Anatomía del Sistema de Nombres de Dominio (DNS) y BIND9
│
├── C. IMPLEMENTACIÓN PRÁCTICA PASO A PASO (04:00 - 12:30)
│   ├── Paso 1: Inspección de interfaces de red del Servidor
│   ├── Paso 2: Instalación de paquetes con APT
│   ├── Paso 3: Configuración del Demonio DHCP (Rangos, Exclusiones y Reserva MAC)
│   ├── Paso 4: Validación de sintaxis e inicio de ISC-DHCP-Server
│   ├── Paso 5: Configuración de BIND9 (Forwarders, Zonas Directa e Inversa)
│   ├── Paso 6: Validación criptográfica y sintáctica de Zonas DNS
│   └── Paso 7: Verificación en tiempo real desde la estación Cliente Debian XFCE
│
└── D. CONCLUSIÓN Y BUENAS PRÁCTICAS (12:30 - 14:00)
    ├── Resumen técnico de la infraestructura implementada
    └── Enlaces al repositorio GitHub y cierre
```

---

## A. INTRODUCCIÓN Y REQUISITOS

### [00:00 - 02:00] Escena 1: Presentación, Arquitectura y Demostración del Resultado Final

* **En pantalla:** Escritorio de Windows con VMware Workstation en pantalla completa mostrando las dos máquinas virtuales divididas equitativamente: a la izquierda `DebianServer` en terminal negra con prompt verde, a la derecha `Debian XFCE` con su escritorio limpio. Cámara web visible en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Hola a todos y bienvenidos a este tutorial de nivel profesional sobre Administración de Servidores Linux. Mi nombre es [Tu Nombre Completo], y el día de hoy vamos a construir desde cero los dos pilares más críticos de cualquier red corporativa: un servidor **DHCP** automatizado y un sistema de nombres de dominio **DNS autoritativo** utilizando **BIND9** sobre **Debian GNU/Linux 12**.*
  > 
  > *Imaginemos por un momento una empresa con cientos de ordenadores. Si tuviéramos que configurar a mano la dirección IP, la máscara, la puerta de enlace y el DNS en cada equipo, no solo perderíamos días de trabajo, sino que el riesgo de generar conflictos de IP duplicadas sería altísimo. Aquí es donde entra en juego el protocolo **DHCP**.*
  > 
  > *Por otro lado, los seres humanos nos comunicamos mediante nombres legibles como `www.empresa.local`, pero los enrutadores y conmutadores solo entienden direcciones IP numéricas como `192.168.50.10`. El servidor **DNS** es la guía telefónica digital que traduce esos nombres instantáneamente.*
  > 
  > *Al finalizar este video, habremos conseguido que nuestro cliente Debian XFCE reciba automáticamente sus parámetros de red, demostraremos cómo aplicar rangos dinámicos, cómo crear exclusiones de seguridad para impresoras y cómo aplicar una reserva fija mediante la dirección física MAC del equipo. Finalmente, resolveremos dominios locales directos, inversos y consultas hacia internet mediante reenviadores recursivos.*
  > 
  > *Para seguir este tutorial solo necesitas dos máquinas virtuales en VMware: nuestro servidor Debian Server configurado con dos tarjetas de red —una NAT para descargar paquetes y una conectada al LAN Segment privado llamado `Lab-Red`— y una máquina cliente Debian XFCE conectada exclusivamente a ese mismo segmento `Lab-Red`. No utilices el modo Puente o Bridged, ya que entrarías en conflicto con el router físico de tu casa. ¡Comencemos!"*

---

## B. FUNDAMENTOS TÉCNICOS ESENCIALES

### [02:00 - 04:00] Escena 2: El Ciclo DORA de DHCP y la Jerarquía de Zonas en BIND9

* **En pantalla:** Diagrama visual de la arquitectura o terminal del servidor preparada para trabajar.
* **Lo que dices verbalmente:**
  > *"Antes de tocar un solo comando, es fundamental entender qué sucede debajo del capó.*
  > 
  > *El protocolo DHCP opera sobre el protocolo de transporte UDP en los puertos 67 para el servidor y 68 para el cliente, mediante un proceso de cuatro pasos conocido por el acrónimo **DORA**:*
  > *1. **Discover (Descubrimiento):** Cuando el cliente arranca sin IP, envía un paquete de difusión masiva o broadcast a la dirección `255.255.255.255` diciendo: '¿Hay algún servidor DHCP en esta red?'.*
  > *2. **Offer (Oferta):** Nuestro servidor Debian escucha la petición y le responde con un paquete Offer diciendo: 'Tengo disponible la IP 192.168.50.100 para ti'.*
  > *3. **Request (Solicitud):** El cliente contesta: 'Acepto formalmente tu oferta de la IP 192.168.50.100'.*
  > *4. **Acknowledge (Reconocimiento):** El servidor sella la concesión en su base de datos de leases y le entrega la configuración completa: IP, máscara `/24`, Gateway `192.168.50.1` y servidor DNS `192.168.50.10`.*
  > 
  > *Por el lado de **DNS**, utilizaremos **BIND9** (Berkeley Internet Name Domain), el estándar de facto en servidores de nombres en internet. En BIND9 manejamos dos conceptos vitales:*
  > *- La **Zona Directa**, que traduce nombres de host a direcciones IPv4 mediante registros de tipo **A** (Address) o alias mediante registros **CNAME** (Canonical Name).*
  > *- La **Zona Inversa**, que hace exactamente lo contrario: a partir de una IP nos devuelve el nombre del equipo mediante registros **PTR** (Pointer). Para ello, la red se nombra invirtiendo los octetos bajo el dominio especial `.in-addr.arpa`, convirtiendo nuestra red `192.168.50.0` en `50.168.192.in-addr.arpa`.*
  > 
  > *Con estos conceptos claros en la mente, pasemos directamente a la consola a configurar los servicios."*

---

## C. IMPLEMENTACIÓN PRÁCTICA PASO A PASO

### [04:00 - 05:15] Paso 1: Inspección y Preparación de Interfaces de Red

* **En pantalla:** Terminal de `Debian Server` ocupando la mitad izquierda.
* **Lo que dices verbalmente:**
  > *"El primer paso crítico antes de instalar cualquier servicio es verificar nuestras tarjetas de red. En nuestro servidor necesitamos dos interfaces físicas virtuales: una que se conecte a internet para descargar paquetes y otra conectada al segmento del laboratorio con una IP fija.*
  > 
  > *Para inspeccionar nuestras tarjetas utilizaremos el comando `ip a`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  ip a
  ```
* **Explicación técnica del comando:**
  > *"El comando `ip` pertenece al paquete moderno `iproute2` y el argumento `a` es la abreviatura de `address`. Nos muestra todas las interfaces disponibles en el kernel Linux.*
  > 
  > *Si observamos la salida en pantalla, tenemos:*
  > *1. La interfaz `lo` o bucle local con la IP `127.0.0.1`.*
  > *2. La interfaz `ens33`, que tiene una IP asignada por NAT para descargar paquetes.*
  > *3. Y la interfaz `ens37`, que es nuestra tarjeta de laboratorio. Observen que ya tiene configurada la IP estática `192.168.50.10/24`.*
  > 
  > *Esta IP estática se configuró en el archivo `/etc/network/interfaces`. Es indispensable que el servidor tenga una IP fija, porque si el servidor DHCP o DNS cambiara de IP con cada reinicio, toda la red se quedaría sin servicio."*

---

### [05:15 - 06:45] Paso 2: Instalación de Paquetes Base con APT

* **En pantalla:** Consola de Debian Server.
* **Lo que dices verbalmente:**
  > *"Ahora que confirmamos nuestra conectividad, procederemos a instalar los paquetes necesarios: `isc-dhcp-server` para el servicio DHCP, y el ecosistema completo de `bind9` junto con sus utilidades y herramientas de diagnóstico `dnsutils`.*
  > 
  > *Ejecutamos el comando:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo apt update && sudo apt install -y isc-dhcp-server bind9 bind9utils bind9-doc dnsutils
  ```
* **Explicación técnica del comando:**
  > *"Analicemos la anatomía de este comando:*
  > *- Utilizamos `sudo` porque la instalación de servicios y modificación de archivos en `/etc` requiere privilegios de superusuario root.*
  > *- `apt update` actualiza los índices locales de repositorios para asegurar que instalemos las versiones más recientes.*
  > *- El operador `&&` es un condicional que solo ejecuta el segundo comando si el primero finalizó con código de éxito cero.*
  > *- El parámetro `-y` responde automáticamente 'sí' a la confirmación de descarga de paquetes.*
  > *- Paquetes instalados: `isc-dhcp-server` es el demonio DHCP oficial del Internet Systems Consortium; `bind9` es el servidor DNS; `bind9utils` nos provee binarios esenciales como `named-checkconf` y `named-checkzone` para validar que nuestros archivos no tengan errores de sintaxis; y `dnsutils` nos proporciona comandos de diagnóstico indispensables como `nslookup` y `dig`.*
  > 
  > *¡Atención a este detalle vital! Al finalizar la instalación, es muy común que Debian muestre un error en color rojo indicando que `isc-dhcp-server.service failed`. No se alarmen: esto es completamente normal. Debian intenta iniciar el servicio automáticamente al instalarlo, pero como todavía no le hemos dicho en qué tarjeta escuchar ni qué rango repartir, el servicio se detiene por seguridad."*

---

### [06:45 - 08:30] Paso 3: Configuración del Servidor DHCP (Rangos, Exclusión y Reserva)

* **En pantalla:** Editor o visualizador mostrando `/etc/default/isc-dhcp-server` y luego `/etc/dhcp/dhcpd.conf`.
* **Lo que dices verbalmente:**
  > *"Para poner en marcha el servidor DHCP debemos configurar dos archivos clave.*
  > 
  > *El primero es `/etc/default/isc-dhcp-server`. Aquí le indicamos al sistema operativo a través de qué tarjeta de red debe emitir los paquetes de concesión.*
  > *Lo abrimos con un editor como `nano` ejecutando `sudo nano /etc/default/isc-dhcp-server`. Buscamos la línea `INTERFACESv4` y nos aseguramos de que tenga el nombre exacto de nuestra tarjeta del laboratorio: `INTERFACESv4="ens37"`.*
  > 
  > *El segundo archivo, y el más importante, es `/etc/dhcp/dhcpd.conf`. Este archivo define las reglas de direccionamiento de toda la empresa.*
  > 
  > *Veamos su contenido en pantalla:"*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat /etc/dhcp/dhcpd.conf
  ```
* **Explicación técnica del archivo `dhcpd.conf`:**
  > *"Desglosemos las directivas fundamentales de este archivo:*
  > *- `default-lease-time 600;` y `max-lease-time 7200;`: Definen el tiempo en segundos que un cliente puede conservar una IP antes de tener que renovarla (10 minutos por defecto, 2 horas como máximo).*
  > *- `authoritative;`: Esta directiva es vital; le indica a la red que este servidor es la autoridad oficial y tiene el poder de rechazar concesiones incorrectas de otros routers intrusos.*
  > 
  > *A continuación declaramos la subred corporativa:*
  > ```text
  > subnet 192.168.50.0 netmask 255.255.255.0 {
  >     range 192.168.50.100 192.168.50.149;
  >     range 192.168.50.161 192.168.50.200;
  >     option routers 192.168.50.1;
  >     option subnet-mask 255.255.255.0;
  >     option domain-name "empresa.local";
  >     option domain-name-servers 192.168.50.10;
  > }
  > ```
  > *- Fíjense en la técnica para crear el rango de exclusión: la práctica nos exige repartir del `.100` al `.200`, pero excluyendo del `.150` al `.160` para equipos fijos como impresoras. En el estándar ISC-DHCP esto se logra de forma elegante dividiendo la directiva `range` en dos bloques: del `.100` al `.149`, y del `.161` al `.200`. De esta manera, el espacio entre el 150 y el 160 queda matemáticamente protegido y jamás será entregado a ningún cliente dinámico.*
  > *- Las opciones `routers` y `domain-name-servers` le inyectan al cliente la puerta de enlace y la IP de nuestro propio servidor DNS.*
  > 
  > *Y justo debajo tenemos el bloque de reserva estática por dirección MAC:*
  > ```text
  > host cliente-vip {
  >     hardware ethernet 00:0c:29:e6:8a:3a;
  >     fixed-address 192.168.50.25;
  > }
  > ```
  > *- `hardware ethernet`: especifica la dirección física irrepetible de la tarjeta de red de nuestra máquina Debian XFCE.*
  > *- `fixed-address`: le asigna de manera permanente e inamovible la IP `192.168.50.25`. Sin importar cuántas veces se reinicie este cliente, siempre recibirá exactamente esta dirección.*
  > 
  > *Antes de reiniciar el servicio, SIEMPRE debemos comprobar que no tengamos errores de sintaxis o puntos y comas faltantes. Para ello ejecutamos:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo dhcpd -t -cf /etc/dhcp/dhcpd.conf
  ```
* **Explicación técnica:**
  > *- El flag `-t` significa 'test syntax' (prueba de sintaxis) y `-cf` indica la ruta del archivo de configuración. Si no arroja ningún mensaje de error, nuestro archivo está impecable."*

---

### [08:30 - 10:30] Paso 4: Configuración de BIND9 (Forwarders, Zona Directa e Inversa)

* **En pantalla:** Terminal de Debian Server mostrando las carpetas de BIND9.
* **Lo que dices verbalmente:**
  > *"Ahora pasamos al motor DNS: BIND9. Su configuración se compone de tres partes: las opciones globales, la declaración de zonas y los archivos de registros.*
  > 
  > *En `/etc/bind/named.conf.options`, configuramos los reenviadores o forwarders. Cuando un cliente consulte por un dominio público como `google.com`, BIND9 no lo tiene en su base de datos local, por lo que reenviará recursivamente la consulta a servidores DNS mundiales como `8.8.8.8` de Google y `1.1.1.1` de Cloudflare.*
  > 
  > *En `/etc/bind/named.conf.local` declaramos nuestras dos zonas:*
  > ```text
  > zone "empresa.local" {
  >     type master;
  >     file "/etc/bind/zones/db.empresa.local";
  > };
  > 
  > zone "50.168.192.in-addr.arpa" {
  >     type master;
  >     file "/etc/bind/zones/db.192.168.50";
  > };
  > ```
  > *- `type master`: indica que este servidor posee la copia autoritativa y original de los registros.*
  > *- `file`: especifica la ruta física donde se almacenan los registros.*
  > 
  > *Inspeccionemos el archivo de zona directa con `cat /etc/bind/zones/db.empresa.local`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat /etc/bind/zones/db.empresa.local
  ```
* **Explicación técnica de registros DNS:**
  > *- El registro **SOA** (Start of Authority) contiene los parámetros de control de la zona, incluyendo el número serial con formato de fecha, indispensable para la replicación entre servidores.*
  > *- Registro **NS** (Name Server): declara a `ns1.empresa.local.` como el servidor de nombres oficial.*
  > *- Registros **A** (Address): vinculan nombres directos a la IP `192.168.50.10`: `ns1`, `server`, `mail` y `www`.*
  > *- Registro **CNAME** (Canonical Name): crea un alias de tal forma que `web.empresa.local` apunte de forma transparente a `www.empresa.local`.*
  > *- Registro **MX** (Mail Exchange): `IN MX 10 mail.empresa.local.` especifica que cualquier correo para el dominio `@empresa.local` debe entregarse al servidor mail con prioridad 10.*
  > *- Registro **TXT** (SPF): `"v=spf1 mx ip4:192.168.50.10 ~all"`, una política de seguridad que le indica al mundo que nuestra IP está autorizada para emitir correos a nombre de la empresa.*
  > 
  > *Y en `/etc/bind/zones/db.192.168.50` definimos los registros **PTR** para la resolución inversa, traduciendo el puntero `10` de vuelta hacia nuestros hosts.*
  > 
  > *Validamos la configuración global y las zonas con las herramientas nativas de BIND9:*
* **Comandos de validación en Debian Server:**
  ```bash
  sudo named-checkconf
  sudo named-checkzone empresa.local /etc/bind/zones/db.empresa.local
  sudo named-checkzone 50.168.192.in-addr.arpa /etc/bind/zones/db.192.168.50
  ```
* **Explicación técnica:**
  > *- `named-checkconf` valida la sintaxis de todos los archivos de configuración de BIND9. Si la terminal regresa limpia sin errores, todo está perfecto.*
  > *- `named-checkzone` carga y valida la estructura de cada zona. Observen cómo la terminal nos responde **OK** y muestra el número serial cargado.*
  > 
  > *Ahora reiniciamos ambos servicios y comprobamos su estado:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart bind9 && sudo systemctl restart isc-dhcp-server
  sudo systemctl status bind9 isc-dhcp-server --no-pager
  ```
* **Resultado:**
  > *- Ambos servicios aparecen en color verde con la etiqueta **active (running)**. ¡Nuestra infraestructura de servidor está lista para recibir clientes!"*

---

### [10:30 - 13:00] Paso 5: Pruebas y Verificación en Vivo desde Debian XFCE Cliente

* **En pantalla:** Enfoque en la máquina de la derecha: `Debian XFCE Cliente`.
* **Lo que dices verbalmente:**
  > *"Ha llegado el momento cumbre: demostrar que nuestro cliente Debian XFCE se comunica a la perfección con el servidor.*
  > 
  > *En Debian XFCE la red es administrada por NetworkManager. Para forzar una renovación completa de parámetros de red y disparar la negociación DHCP, ejecutamos en la terminal del cliente:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  sudo nmcli networking off && sudo nmcli networking on
  ```
* **Explicación técnica:**
  > *- `nmcli` es la interfaz de línea de comandos de NetworkManager. Al apagar y encender la red, la interfaz `ens33` del cliente inicia de inmediato la secuencia DORA.*
  > 
  > *Comprobemos qué dirección IP nos entregó el servidor ejecutando `ip a`:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  ip a
  ```
* **Interpretación del resultado:**
  > *- ¡Miren la salida en pantalla! En la interfaz `ens33` observamos dos datos cruciales:*
  > *1. La dirección física MAC es `00:0c:29:e6:8a:3a`.*
  > *2. La dirección IPv4 asignada es exactamente `192.168.50.25/24` con la bandera `dynamic noprefixroute`.*
  > *Esto comprueba de forma contundente que el servidor DHCP reconoció la MAC de esta máquina y le aplicó la directiva de reserva fija `host cliente-vip`.*
  > 
  > *Ahora comprobemos la configuración del DNS con `cat /etc/resolv.conf`:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  cat /etc/resolv.conf
  ```
* **Interpretación del resultado:**
  > *- Observen la línea `nameserver 192.168.50.10` y `search empresa.local`. El servidor DHCP le transmitió con éxito al cliente la ubicación de nuestro servidor BIND9.*
  > 
  > *Ahora realizaremos las 4 pruebas de fuego con `nslookup` y `dig`:*
* **Comandos a ejecutar consecutivamente en Debian XFCE Cliente:**
  ```bash
  # 1. Resolución Directa (Registros A y CNAME)
  nslookup www.empresa.local 192.168.50.10
  nslookup ftp.empresa.local 192.168.50.10

  # 2. Resolución Inversa (Puntero PTR)
  nslookup 192.168.50.10 192.168.50.10

  # 3. Consulta de Registro de Correo MX
  dig @192.168.50.10 empresa.local MX +short

  # 4. Prueba de Reenviadores Recursivos a Internet (Forwarders)
  nslookup google.com 192.168.50.10
  ```
* **Explicación e interpretación de cada resultado:**
  > *- En la primera prueba, `www.empresa.local` y `ftp.empresa.local` nos devuelven de inmediato la IP `192.168.50.10` con respuesta autoritativa.*
  > *- En la segunda prueba, al consultar la IP numérica `192.168.50.10`, BIND9 consulta su zona inversa `50.168.192.in-addr.arpa` y nos devuelve de vuelta todos los nombres canónicos asociados: `ns1`, `server`, `mail`, `www` y `ftp.empresa.local.`.*
  > *- En la tercera prueba, `dig` nos confirma que el servidor de intercambio de correo es `mail.empresa.local` con prioridad 10.*
  > *- Y en la cuarta prueba, al consultar un dominio mundial como `google.com`, nuestro servidor BIND9 se comunica con los servidores públicos `8.8.8.8` y nos entrega la respuesta exitosa en el cliente. ¡Todo funciona con precisión de reloj suizo!"*

---

## D. CONCLUSIÓN Y BUENAS PRÁCTICAS

### [13:00 - 14:00] Escena 6: Resumen Técnico y Cierre del Video

* **En pantalla:** Cámara web en primer plano o escritorio mostrando ambas máquinas con las salidas exitosas.
* **Lo que dices verbalmente:**
  > *"En este tutorial hemos demostrado la implementación completa y profesional de la infraestructura base de red en Linux:*
  > *1. Instalamos y securizamos **ISC-DHCP-Server**, configurando rangos dinámicos, aislando rangos excluidos para proteger equipamiento de oficina y fijando direcciones IP mediante reservas estáticas por hardware MAC.*
  > *2. Desplegamos **BIND9**, configurando zonas autoritativas directas, zonas inversas mediante punteros PTR y reenviadores recursivos que aseguran la navegación hacia internet.*
  > *3. Validamos cada archivo con herramientas de comprobación de sintaxis como `named-checkconf`, `named-checkzone` y `dhcpd -t` antes de reiniciar los servicios, una práctica indispensable en entornos de producción.*
  > 
  > *Todos los archivos de configuración comentados, el manual de pruebas y la memoria técnica en PDF se encuentran disponibles en el repositorio público de GitHub cuyo enlace encontrarán en la descripción de este video.*
  > 
  > *Si este video les fue de utilidad, no olviden dejar su pulgar arriba, suscribirse al canal y dejar sus dudas en la caja de comentarios. Mi nombre es [Tu Nombre Completo] y nos vemos en el próximo video donde desplegaremos un stack web seguro con Apache, MariaDB, PHP y FTP enjaulado. ¡Hasta la próxima!"*
