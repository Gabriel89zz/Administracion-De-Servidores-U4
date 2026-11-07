# Guion Maestro de Demostración Técnica: Tarea 4.1 (Servidor DHCP e Infraestructura DNS BIND9)

> **Perfil del Expositor:** Ingeniero en Sistemas y Administrador de Infraestructura de Servidores Linux.  
> **Tono y Estilo:** Formal, técnico, académico, riguroso y didáctico. Sin frases informales ni modismos de redes sociales.  
> **Formato de Grabación:** OBS Studio con captura de pantalla limpia y **cámara web activa en la esquina inferior derecha** durante toda la demostración.  
> **Disposición del Entorno en Pantalla:** Dos máquinas virtuales en VMware Workstation:
> - **Lado Izquierdo:** `Debian Server` (Servidor de infraestructura base sin entorno gráfico). IP fija: `192.168.50.10`.
> - **Lado Derecho:** `Debian XFCE Cliente` (Estación de trabajo cliente). MAC: `00:0c:29:e6:8a:3a`.
> **Segmento de Red:** VMware LAN Segment privado `Lab-Red` (`192.168.50.0/24`).  
> **Duración estimada:** 12 a 15 minutos.

---

## ESTRUCTURA DE LA DEMOSTRACIÓN

```
├── A. INTRODUCCIÓN Y ARQUITECTURA DE LABORATORIO (00:00 - 02:30)
│   ├── Identificación de la práctica y objetivos de infraestructura
│   ├── Justificación técnica del uso de dos máquinas virtuales
│   ├── Independencia del sistema operativo cliente (Linux / Windows)
│   └── Configuración de red aislada en VMware (LAN Segment vs NAT/Bridged)
│
├── B. FUNDAMENTOS TÉCNICOS Y PROTOCOLOS (02:30 - 04:30)
│   ├── Mecánica del protocolo DHCP: Ciclo de cuatro fases DORA (UDP 67/68)
│   └── Estructura jerárquica de BIND9: Zonas autoritativas e inversas (.in-addr.arpa)
│
├── C. DESARROLLO, ANÁLISIS LÍNEA POR LÍNEA Y PRUEBAS EN VIVO (04:30 - 13:30)
│   ├── Paso 1: Verificación de direccionamiento en el Servidor (ip a)
│   ├── Paso 2: Instalación de paquetes de infraestructura con APT
│   ├── Paso 3: Configuración de la interfaz DHCP (/etc/default/isc-dhcp-server)
│   ├── Paso 4: Desglose exhaustivo línea por línea de /etc/dhcp/dhcpd.conf
│   ├── Paso 5: Desglose línea por línea de BIND9 (Options, Zonas Directa e Inversa)
│   ├── Paso 6: Validación estricta de sintaxis e inicio de demonios
│   └── Paso 7: Comprobación y auditoría en vivo desde la estación cliente
│
└── D. CONCLUSIONES TÉCNICAS Y CIERRE FORMAL (13:30 - 14:30)
    ├── Validación de cumplimiento de requerimientos
    └── Disponibilidad de artefactos en el repositorio y despedida formal
```

---

## A. INTRODUCCIÓN Y ARQUITECTURA DE LABORATORIO

### [00:00 - 02:30] Escena 1: Presentación, Justificación del Entorno y Modelo Cliente/Servidor

* **En pantalla:** Escritorio mostrando las dos máquinas virtuales en paralelo sobre VMware Workstation: a la izquierda la consola de `Debian Server` y a la derecha el escritorio de `Debian XFCE`. Cámara web visible en la esquina inferior derecha.
* **Lo que dices verbalmente:**
  > *"Estimado profesor y compañeros, les doy la bienvenida a la presentación técnica de la Tarea 4.1 de la materia Administración de Servidores, correspondiente a la Unidad 4: Servidores DNS, Web, FTP, DHCP y Correo Electrónico. Mi nombre es [Tu Nombre Completo].*
  > 
  > *El propósito central de esta práctica es implementar, configurar y auditar los dos servicios esenciales de infraestructura que gobiernan las comunicaciones en una red corporativa: el protocolo de configuración dinámica de host a través de **ISC-DHCP-Server**, y la resolución jerárquica de nombres mediante el servidor autoritativo **BIND9** sobre **Debian GNU/Linux 12**.*
  > 
  > *Antes de analizar la configuración, es fundamental responder a una pregunta técnica clave: **¿Por qué estamos utilizando dos máquinas virtuales independientes en este laboratorio?***
  > *La razón es que un servicio de red nunca debe evaluarse de forma aislada sobre la misma máquina que lo aloja. Utilizar dos máquinas virtuales recrea con fidelidad la arquitectura cliente/servidor del mundo real:*
  > *1. **Aislamiento de roles:** El servidor actúa como el proveedor central de recursos y directivas, mientras que el cliente opera como el nodo consumidor.*
  > *2. **Pruebas de difusión real en capa de enlace:** El protocolo DHCP opera mediante paquetes de broadcast (`255.255.255.255`). Para demostrar que el servidor responde correctamente a una solicitud de red auténtica, el paquete debe atravesar un medio de transmisión virtualizado entre dos adaptadores de red distintos.*
  > *3. **Independencia del Sistema Operativo Cliente:** Aunque en este laboratorio estamos utilizando una máquina cliente con **Debian XFCE**, es imperativo aclarar que el cliente podría ser indistintamente una estación de trabajo con **Windows 10, Windows 11, Ubuntu o cualquier otra distribución Linux**. Los protocolos DHCP y DNS son estándares abiertos definidos por RFCs del IETF (específicamente RFC 2131 para DHCP y RFC 1034 y 1035 para DNS). El servidor responde de manera universal e idéntica a cualquier sistema operativo que respete el estándar.*
  > 
  > *Ambas máquinas están interconectadas a través de un **LAN Segment** en VMware denominado `Lab-Red`, operando en la subred privada `192.168.50.0/24`. Este aislamiento garantiza que nuestro servidor DHCP no colisione con el enrutador físico doméstico ni emita concesiones indebidas fuera del laboratorio."*

---

## B. FUNDAMENTOS TÉCNICOS Y PROTOCOLOS

### [02:30 - 04:30] Escena 2: Mecánica de Concesión DORA y Jerarquía DNS

* **En pantalla:** Terminal del servidor lista o diagrama de arquitectura.
* **Lo que dices verbalmente:**
  > *"Analicemos brevemente el funcionamiento técnico de ambos protocolos a nivel de paquetes de red.*
  > 
  > *El protocolo **DHCP** opera sobre el protocolo de transporte UDP, utilizando el puerto de destino 67 en el servidor y el puerto 68 en el cliente. Su funcionamiento se basa en una máquina de estados de cuatro fases denominada **DORA**:*
  > *1. **Discover (Descubrimiento):** El cliente, careciendo de dirección IP, genera un paquete UDP con dirección IP de origen `0.0.0.0` y dirección de destino `255.255.255.255`, interrogando a la red sobre la presencia de un servidor DHCP.*
  > *2. **Offer (Oferta):** El servidor `isc-dhcp-server`, tras consultar su pool disponible en memoria, emite una oferta conteniendo una dirección IP tentativa, la máscara de subred y el tiempo de concesión.*
  > *3. **Request (Solicitud):** El cliente responde formalizando la aceptación de la oferta específica emitida por el servidor.*
  > *4. **Acknowledge (Reconocimiento o ACK):** El servidor ratifica la concesión, escribe el registro en su base de datos de leases en `/var/lib/dhcp/dhcpd.leases` y transfiere los parámetros de red secundarios, como la puerta de enlace predeterminada y el servidor DNS corporativo.*
  > 
  > *Por su parte, **BIND9** (Berkeley Internet Name Domain) actúa como servidor de nombres autoritativo para el dominio `empresa.local`. Su arquitectura desacopla dos tipos de zonas:*
  > *- La **Zona Directa**, que mapea nombres de host a direcciones numéricas IPv4 mediante registros de recurso tipo **A** (Address), y genera alias mediante registros **CNAME** (Canonical Name).*
  > *- La **Zona Inversa**, que resuelve la dirección IP de origen hacia su nombre de host canónico mediante registros **PTR** (Pointer). Para implementar esta zona, el espacio de direcciones `192.168.50.0` se invierte estructurándose bajo el árbol especializado `50.168.192.in-addr.arpa`.*
  > 
  > *Pasemos ahora a la implementación técnica detallada en el servidor."*

---

## C. DESARROLLO, ANÁLISIS LÍNEA POR LÍNEA Y PRUEBAS EN VIVO

### [04:30 - 05:45] Paso 1: Verificación de Interfaces de Red en el Servidor

* **En pantalla:** Consola de `Debian Server` en el lado izquierdo.
* **Lo que dices verbalmente:**
  > *"Iniciamos sesión administrativa en `Debian Server`. Lo primero que debemos constatar es la correcta configuración de las interfaces de red del servidor utilizando el comando `ip a`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  ip a
  ```
* **Explicación técnica del comando:**
  > *- `ip` es la herramienta estándar del paquete `iproute2` para la gestión de pilas de red en el kernel Linux, y el argumento `a` (abreviatura de `address`) enumera el estado de los enlaces y sus direcciones asignadas.*
  > 
  > *Al inspeccionar la salida en pantalla observamos tres adaptadores:*
  > *1. `lo`: La interfaz de bucle local con la dirección `127.0.0.1/8`.*
  > *2. `ens33`: Nuestra primera interfaz de red virtual, configurada bajo NAT, permitiendo la comunicación externa hacia repositorios de paquetes.*
  > *3. `ens37`: Nuestra segunda interfaz de red física virtual, vinculada al LAN Segment `Lab-Red`. Observamos que tiene asignada de forma estática la dirección `192.168.50.10/24`.*
  > 
  > *Es un requisito riguroso que todo servidor de infraestructura posea una IP fija e inmutable, ya que de lo contrario los clientes perderían la referencia de red ante cualquier reinicio del sistema."*

---

### [05:45 - 07:00] Paso 2: Instalación de Paquetes de Infraestructura con APT

* **En pantalla:** Consola de Debian Server.
* **Lo que dices verbalmente:**
  > *"Procedemos con la instalación de los paquetes requeridos desde los repositorios oficiales de Debian ejecutando el siguiente comando con privilegios de superusuario:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo apt update && sudo apt install -y isc-dhcp-server bind9 bind9utils bind9-doc dnsutils
  ```
* **Explicación técnica del comando:**
  > *- `sudo`: Eleva la ejecución con privilegios de root para modificar las rutas del sistema en `/etc` y `/var`.*
  > *- `apt update`: Sincroniza los índices locales con los servidores de paquetes de Debian.*
  > *- El operador `&&`: Garantiza la ejecución secuencial únicamente si la actualización concluye con éxito.*
  > *- `apt install -y`: Instala los paquetes respondiendo afirmativamente de forma no interactiva.*
  > *- Los paquetes seleccionados son:*
  >   - `isc-dhcp-server`: El demonio `dhcpd` desarrollado por el Internet Systems Consortium.*
  >   - `bind9`: El demonio `named` que implementa el servicio DNS.*
  >   - `bind9utils`: Conjunto de herramientas administrativas indispensables, tales como `named-checkconf` y `named-checkzone`.*
  >   - `dnsutils`: Paquete que provee utilidades cliente de diagnóstico DNS como `nslookup` y `dig`.*
  > 
  > *Nota técnica: Al finalizar la instalación de `isc-dhcp-server`, Debian intentará levantar el servicio y reportará un fallo de inicialización. Esto es un comportamiento normal y esperado, ya que el archivo de configuración aún no contiene una subred declarada que coincida con ninguna interfaz física del servidor."*

---

### [07:00 - 09:30] Paso 3: Análisis Detallado Línea por Línea de `dhcpd.conf`

* **En pantalla:** Terminal de Debian Server mostrando el archivo `/etc/dhcp/dhcpd.conf` con numeración de líneas.
* **Lo que dices verbalmente:**
  > *"Antes de configurar las directivas del pool, en el archivo `/etc/default/isc-dhcp-server` definimos la variable `INTERFACESv4="ens37"` para indicarle al demonio que enlace sus sockets exclusivamente a la interfaz del laboratorio.*
  > 
  > *Para inspeccionar de manera limpia y profesional el archivo principal de configuración sin riesgo de alterar accidentalmente sus directivas, utilizaremos el comando `cat -n`. El parámetro `-n` numera cada una de las líneas, lo que nos permite auditar su estructura con absoluta precisión:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n /etc/dhcp/dhcpd.conf
  ```
* **Explicación detallada línea por línea:**
  > *"Analicemos la estructura técnica del archivo en pantalla:*
  > 
  > *- **Línea 5 (`option domain-name "empresa.local";`):** Define el sufijo DNS predeterminado que el cliente anexará a cualquier consulta de nombre no calificado.*
  > *- **Línea 8 (`option domain-name-servers 192.168.50.10;`):** Le transmite al cliente la dirección IP de nuestro servidor DNS BIND9, consolidando la integración entre ambos servicios.*
  > *- **Línea 11 (`default-lease-time 600;`):** Establece el tiempo de concesión estándar en 600 segundos (10 minutos), momento en el cual el cliente intentará renovar su IP.*
  > *- **Línea 12 (`max-lease-time 7200;`):** Fija el límite máximo de concesión en 7200 segundos (2 horas) si el cliente solicita una extensión.*
  > *- **Línea 15 (`authoritative;`):** Indica formalmente que este servidor es la autoridad de direccionamiento oficial para este segmento de red, permitiéndole enviar paquetes `DHCPNAK` para revocar concesiones obsoletas.*
  > 
  > *A partir de la línea 18 declaramos el bloque de red principal:*
  > *- **Línea 18 (`subnet 192.168.50.0 netmask 255.255.255.0 {`):** Define el alcance de la red Clase C y su máscara de 24 bits.*
  > *- **Líneas 21 y 23:** Observen con atención cómo implementamos la exclusión exigida en la práctica:*
  >   - `range 192.168.50.100 192.168.50.149;`
  >   - `range 192.168.50.161 192.168.50.200;`
  >   *El requerimiento solicita entregar direcciones dinámicas en el rango .100 al .200, pero protegiendo las direcciones .150 a la .160 para dispositivos fijos como impresoras corporativas. En lugar de directivas complejas, dividimos el pool en dos bloques contiguos. El intervalo del 150 al 160 queda matemáticamente fuera de la asignación dinámica.*
  > *- **Línea 26 (`option routers 192.168.50.1;`):** Inyecta la puerta de enlace predeterminada hacia la tabla de enrutamiento del cliente.*
  > *- **Línea 29 (`option broadcast-address 192.168.50.255;`):** Especifica la dirección de difusión del segmento.*
  > 
  > *A partir de la línea 40 observamos la reserva estática por hardware:*
  > ```text
  > 40: host cliente-vip {
  > 41:     hardware ethernet 00:0c:29:e6:8a:3a;
  > 42:     fixed-address 192.168.50.25;
  > 43:     option host-name "cliente-vip.empresa.local";
  > 44: }
  > ```
  > *- `hardware ethernet`: Vincula la dirección física MAC del cliente.*
  > *- `fixed-address`: Obliga al servidor a entregarle siempre la dirección IP fija `192.168.50.25`, asegurando disponibilidad determinista para equipos de alta prioridad.*
  > 
  > *Antes de iniciar el servicio, ejecutamos la comprobación de sintaxis:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo dhcpd -t -cf /etc/dhcp/dhcpd.conf
  ```
* **Explicación técnica:**
  > *- El flag `-t` ejecuta la prueba de análisis sintáctico sobre el archivo especificado por `-cf`. La ausencia de advertencias confirma la integridad del archivo."*

---

### [09:30 - 11:30] Paso 5: Desglose Línea por Línea de BIND9 (Opciones, Zonas y Registros)

* **En pantalla:** Consola de Debian Server mostrando los archivos de BIND9 con `cat -n`.
* **Lo que dices verbalmente:**
  > *"Continuamos con la auditoría de BIND9. Inspeccionamos primero `/etc/bind/named.conf.options` mediante `cat -n`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n /etc/bind/named.conf.options
  ```
* **Explicación técnica línea por línea:**
  > *- **Líneas 8 y 9 (`allow-query` y `allow-recursion`):** Restringen las consultas y la recursión únicamente al localhost y a la subred corporativa `192.168.50.0/24`, evitando ataques de amplificación DNS desde redes externas.*
  > *- **Líneas 12 a 15 (`forwarders { 8.8.8.8; 1.1.1.1; };`):** Define los servidores DNS públicos a los cuales BIND9 consultará recursivamente cuando se le soliciten dominios que no pertenezcan a `empresa.local`.*
  > *- **Línea 24 (`listen-on port 53 { 127.0.0.1; 192.168.50.10; };`):** Enlaza el demonio a los sockets locales en el puerto estándar UDP y TCP 53.*
  > 
  > *En `/etc/bind/named.conf.local` declaramos formalmente las zonas:*
  > ```text
  > zone "empresa.local" {
  >     type master;
  >     file "/etc/bind/zones/db.empresa.local";
  > };
  > zone "50.168.192.in-addr.arpa" {
  >     type master;
  >     file "/etc/bind/zones/db.192.168.50";
  > };
  > ```
  > *- `type master`: Declara a este nodo como la fuente primaria autoritativa de datos.*
  > *- `file`: Establece la ruta absoluta al archivo de registros.*
  > 
  > *Inspeccionemos ahora la zona directa ejecutando `cat -n /etc/bind/zones/db.empresa.local`:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  cat -n /etc/bind/zones/db.empresa.local
  ```
* **Explicación técnica de registros DNS:**
  > *- **Línea 5 (`$TTL 86400`):** Establece el tiempo de vida en caché estándar (24 horas) para los registros.*
  > *- **Línea 6 (`@ IN SOA ns1.empresa.local. admin.empresa.local.`):** Registro Start of Authority. El `@` representa el origen (`empresa.local`), seguido del servidor primario y la dirección de correo del administrador (donde el punto sustituye al `@`).*
  > *- **Línea 7 (`2026110501`):** Número de serie en formato `AAAAMMDD` más dos dígitos de versión. Indispensable incrementarlo cada vez que se modifique un registro.*
  > *- **Línea 16 (`@ IN NS ns1.empresa.local.`):** Declara el servidor de nombres autorizado.*
  > *- **Líneas 21 a 26 (Registros A):** Mapean los nombres `ns1`, `server`, `mail`, `ftp` y `www` hacia la dirección `192.168.50.10`.*
  > *- **Línea 31 (`web IN CNAME www.empresa.local.`):** Crea un alias canónico para que `web` resuelva hacia `www`.*
  > *- **Línea 37 (`@ IN MX 10 mail.empresa.local.`):** Define el intercambiador de correo corporativo con prioridad de coste 10.*
  > *- **Línea 42 (`@ IN TXT "v=spf1 mx ip4:192.168.50.10 ~all"`):** Registro de seguridad Sender Policy Framework que valida la legitimidad de los correos emitidos por el dominio.*
  > 
  > *Procedemos a la validación rigurosa de sintaxis de ambas zonas:*
* **Comandos de validación en Debian Server:**
  ```bash
  sudo named-checkconf
  sudo named-checkzone empresa.local /etc/bind/zones/db.empresa.local
  sudo named-checkzone 50.168.192.in-addr.arpa /etc/bind/zones/db.192.168.50
  ```
* **Explicación técnica:**
  > *- `named-checkconf`: Verifica la sintaxis global de los archivos de BIND9. Una salida limpia indica ausencia de errores.*
  > *- `named-checkzone`: Carga y analiza los registros de la zona en memoria. La respuesta en pantalla nos devuelve formalmente **OK** y el número de serie cargado.*
  > 
  > *Reiniciamos y confirmamos el estado de ejecución de ambos servicios:*
* **Comando a ejecutar en Debian Server:**
  ```bash
  sudo systemctl restart bind9 isc-dhcp-server
  sudo systemctl status bind9 isc-dhcp-server --no-pager
  ```
* **Resultado:**
  > *- Ambos demonios reportan el estado operativo en color verde: **active (running)**."*

---

### [11:30 - 13:30] Paso 6: Verificación y Auditoría de Red desde el Cliente

* **En pantalla:** Enfoque en la máquina derecha: `Debian XFCE Cliente`.
* **Lo que dices verbalmente:**
  > *"Nos trasladamos a la estación de trabajo cliente con Debian XFCE. Procedemos a forzar la renegociación del enlace de red para disparar la secuencia DORA contra nuestro servidor DHCP:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  sudo nmcli networking off && sudo nmcli networking on
  ```
* **Explicación técnica del comando:**
  > *- `nmcli` interactúa con el demonio NetworkManager. Al reiniciar la pila de red, el adaptador `ens33` libera cualquier estado anterior y emite el paquete `DHCPDISCOVER` en el segmento virtual.*
  > 
  > *Auditemos los parámetros obtenidos ejecutando `ip a`:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  ip a
  ```
* **Interpretación técnica de la salida:**
  > *- En la tarjeta `ens33` constatamos dos hechos indiscutibles:*
  > *1. La dirección física MAC del adaptador es `00:0c:29:e6:8a:3a`.*
  > *2. La dirección IPv4 concedida es exactamente `192.168.50.25/24`.*
  > *Esto demuestra que el servidor DHCP procesó la directiva `host cliente-vip`, reconoció la dirección MAC física y le otorgó la IP reservada.*
  > 
  > *Inspeccionemos la resolución DNS asignada con `cat /etc/resolv.conf`:*
* **Comando a ejecutar en Debian XFCE Cliente:**
  ```bash
  cat /etc/resolv.conf
  ```
* **Interpretación:**
  > *- El archivo refleja `nameserver 192.168.50.10` y `search empresa.local`, demostrando la correcta inyección de opciones DHCP.*
  > 
  > *Ejecutemos ahora las cuatro pruebas de validación DNS:*
* **Comandos a ejecutar consecutivamente en Debian XFCE Cliente:**
  ```bash
  # 1. Resolución Directa A y CNAME
  nslookup www.empresa.local 192.168.50.10
  nslookup ftp.empresa.local 192.168.50.10

  # 2. Resolución Inversa PTR
  nslookup 192.168.50.10 192.168.50.10

  # 3. Consulta de Registro MX
  dig @192.168.50.10 empresa.local MX +short

  # 4. Prueba de Reenviadores Recursivos a Internet (Forwarders)
  nslookup google.com 192.168.50.10
  ```
* **Explicación de cada resultado:**
  > *- Las consultas directas a `www` y `ftp` resuelven a `192.168.50.10` de forma autoritativa.*
  > *- La resolución inversa sobre la IP numérica `192.168.50.10` nos devuelve los nombres canónicos asociados mediante la zona `.in-addr.arpa`.*
  > *- El comando `dig` confirma el host intercambiador de correo `mail.empresa.local` con coste 10.*
  > *- Y la consulta a `google.com` demuestra que BIND9 reenvía recursivamente la petición hacia los servidores públicos `8.8.8.8`, entregando la respuesta al cliente sin demoras."*

---

## D. CONCLUSIONES TÉCNICAS Y CIERRE FORMAL

### [13:30 - 14:30] Escena 7: Síntesis de Resultados y Cierre

* **En pantalla:** Cámara web en primer plano con el entorno virtualizado de fondo.
* **Lo que dices verbalmente:**
  > *"Con estas pruebas prácticas concluimos formalmente la verificación técnica de la Tarea 4.1.*
  > 
  > *Podemos sintetizar los logros de ingeniería obtenidos en tres puntos:*
  > *1. **Automatización Determinista de Red:** Desplegamos **ISC-DHCP-Server** configurando pools dinámicos segmentados, rangos de exclusión protegidos para equipamiento fijo y reservas deterministas basadas en la dirección física MAC del cliente.*
  > *2. **Resolución de Nombres Integral:** Implementamos **BIND9** como servidor autoritativo para zonas directas e inversas, incorporando registros de servicios esenciales como MX y SPF, e integrando reenviadores recursivos hacia internet.*
  > *3. **Validación de la Arquitectura Cliente/Servidor:** Verificamos el desacoplamiento total entre el nodo servidor y el nodo cliente, demostrando la interoperabilidad del estándar independientemente del sistema operativo empleado en la estación de trabajo.*
  > 
  > *Todos los archivos de configuración, manuales de pruebas y reportes técnicos con capturas de evidencia se encuentran disponibles y auditables en el repositorio institucional de GitHub.*
  > 
  > *Agradezco su atención a esta presentación técnica."*
