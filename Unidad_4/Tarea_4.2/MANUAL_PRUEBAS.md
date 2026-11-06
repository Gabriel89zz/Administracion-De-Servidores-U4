# Manual de Pruebas: Tarea 4.2 (Stack Web HTTPS y Servidor FTP vsftpd)

Este manual contiene las instrucciones paso a paso con los comandos y configuraciones exactas para desplegar y verificar el stack de servicios web seguro (Apache2 + PHP + MariaDB + SSL) y el servidor de transferencia de archivos seguro (**vsftpd**) con aislamiento de usuarios (**Chroot Jail**) en Debian Server, probado desde la estación de trabajo **Debian XFCE** (o Windows).

---

## 1. Despliegue en el Servidor Debian

### Paso 1.1: Instalación de Paquetes
Ejecuta en Debian Server:
```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mysql mariadb-server vsftpd openssl
```

### Paso 1.2: Configuración de la Base de Datos MariaDB
Ingresa a la consola de MariaDB:
```bash
sudo mysql -u root
```
Ejecuta las siguientes instrucciones SQL:
```sql
CREATE DATABASE IF NOT EXISTS empresa_db;
CREATE USER IF NOT EXISTS 'empresa_user'@'localhost' IDENTIFIED BY 'PasswordSegura123!';
GRANT ALL PRIVILEGES ON empresa_db.* TO 'empresa_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Paso 1.3: Generación de Certificados SSL/TLS con SAN
Ubícate en la carpeta de la tarea dentro del repositorio:
```bash
cd ~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.2/

# Dar permisos de ejecución y generar el certificado
chmod +x generar_certificados.sh
sudo bash generar_certificados.sh
```
> Verifica que existan los archivos `/etc/ssl/certs/empresa.local.crt` y `/etc/ssl/private/empresa.local.key`.

### Paso 1.4: Despliegue del Sitio Web y Virtual Hosts
Desde la misma carpeta `~/Administracion-De-Servidores-U4/Unidad_4/Tarea_4.2/`:
```bash
# Crear directorio raíz del sitio corporativo
sudo mkdir -p /var/www/empresa.local/public_html

# Copiar las páginas de prueba a la raíz web
sudo cp index.php db_test.php /var/www/empresa.local/public_html/
sudo chown -R www-data:www-data /var/www/empresa.local/
sudo chmod -R 755 /var/www/empresa.local/

# Copiar archivos de configuración de Apache
sudo cp empresa.local.conf /etc/apache2/sites-available/
sudo cp empresa.local-ssl.conf /etc/apache2/sites-available/

# Habilitar módulos necesarios y los Virtual Hosts
sudo a2enmod ssl rewrite
sudo a2ensite empresa.local.conf empresa.local-ssl.conf
sudo a2dissite 000-default.conf

# Validar sintaxis de Apache
sudo apache2ctl configtest
# Debe responder: Syntax OK

# Reiniciar Apache
sudo systemctl restart apache2
```

### Paso 1.5: Configuración de vsftpd y Creación del Usuario Enjaulado
```bash
# Respaldar y copiar configuración de vsftpd
sudo cp vsftpd.conf /etc/vsftpd.conf

# Crear usuario ftpuser con contraseña Alumno123! de forma directa y no interactiva
sudo adduser --gecos "" --disabled-password ftpuser
echo "ftpuser:Alumno123!" | sudo chpasswd
sudo usermod -s /bin/bash ftpuser

# Estructurar la jaula chroot (permisos seguros de vsftpd)
sudo mkdir -p /home/ftpuser/ftp/archivos
sudo chown nobody:nogroup /home/ftpuser/ftp
sudo chmod a-w /home/ftpuser/ftp
sudo chown ftpuser:ftpuser /home/ftpuser/ftp/archivos

# Crear un archivo de bienvenida dentro de la carpeta escribible
echo "Bienvenido al directorio seguro del servidor FTP" | sudo tee /home/ftpuser/ftp/archivos/leeme.txt

# Reiniciar vsftpd y verificar estado
sudo systemctl restart vsftpd
sudo systemctl status vsftpd --no-pager
```

---

## 2. Pruebas del Servidor Web HTTPS desde el Cliente

### Paso 2.1: Navegación Segura en el Navegador Web (Firefox en Debian XFCE / Chrome en Windows)
1. Abre el navegador web en tu cliente Debian XFCE.
2. Ingresa a: `https://www.empresa.local` (o `https://192.168.50.10`)
3. Aparecerá la pantalla de advertencia de seguridad porque el certificado es autofirmado (*Self-Signed*). Haz clic en **Avanzado** y luego en **Aceptar el riesgo y continuar**.
4. **Verificación visual del certificado:**
   - Haz clic en el ícono del candado junto a la URL.
   - Selecciona **Conexión no segura / Más información / Ver certificado**.
   - Revisa la pestaña de detalles:
     - **Emitido para:** `empresa.local`.
     - **Organización:** `Empresa Corporativa S.A.`.
     - **Subject Alternative Names (SAN):** `empresa.local`, `www.empresa.local`, `192.168.50.10`.
5. **Verificación del entorno PHP y MariaDB:**
   - En la página principal observarás el banner azul con la leyenda: **🔒 HTTPS (Cifrado Activo)**.
   - En la tabla de detalles debe aparecer:
     - Versión de PHP (ej. `PHP v8.2`).
     - Base de datos MariaDB: **Conectado exitosamente a MariaDB (v10.x/11.x)** en color verde.
6. **Prueba de API JSON de la Base de Datos:**
   - Navega a `https://www.empresa.local/db_test.php`.
   - Devuelve un JSON estructurado con los registros consultados en la tabla `empleados`.

### Paso 2.2: Prueba de Redirección HTTP a HTTPS
1. En el navegador escribe: `http://www.empresa.local` (especificando `http://`).
2. Comprueba que el navegador es redirigido automáticamente a `https://www.empresa.local` con código permanente 301.

---

## 3. Pruebas de Transferencia de Archivos (vsftpd) y Aislamiento Chroot

Puedes realizar estas pruebas mediante **FileZilla** o desde la **terminal** del cliente.

### Paso 3.1: Prueba de Acceso Anónimo Bloqueado
En la terminal del cliente (Debian XFCE o Windows) ejecuta:
```bash
ftp 192.168.50.10
```
- Cuando pida `Name / Usuario`, ingresa: `anonymous`
- Cuando pida `Password / Contraseña`, presiona Enter.
> **Resultado esperado:** El servidor responde: `530 Permission denied` o `Login incorrect`. El acceso anónimo está completamente denegado.

### Paso 3.2: Inicio de Sesión con Usuario Autenticado
En la misma consola:
```bash
ftp 192.168.50.10
```
- `Name`: `ftpuser`
- `Password`: `Alumno123!`
> **Resultado esperado:** Mensaje de bienvenida corporativo y `230 User logged in`. Se muestra el prompt `ftp>`.

### Paso 3.3: Demostración del Enjaulamiento (Chroot Jail)
En el prompt de FTP ejecuta:
```text
ftp> pwd
```
> Responde: `257 "/" is current directory.` (Para el usuario, su directorio raíz es `/`).

Ahora intenta escapar hacia el sistema operativo:
```text
ftp> cd ..
ftp> pwd
ftp> cd /etc
```
> **Resultado esperado:**
> El comando `pwd` sigue respondiendo `"/"`.
> El comando `cd /etc` responde: `550 Failed to change directory`.
> **Conclusión:** El usuario está totalmente aislado. Es imposible que acceda a carpetas críticas del sistema.

### Paso 3.4: Subida y Descarga de Archivos

#### Opción A: Por Terminal
1. Ingresa a la carpeta escribible:
   ```text
   ftp> cd archivos
   ftp> ls
   ```
2. Descarga el archivo de bienvenida:
   ```text
   ftp> get leeme.txt
   ```
3. Sube un archivo de prueba (crea primero un archivo en otra terminal con `echo "prueba" > archivo_cliente.txt`):
   ```text
   ftp> put archivo_cliente.txt
   ftp> ls
   ftp> bye
   ```

#### Opción B: Con FileZilla Gráfico (en Debian XFCE)
1. Abre **FileZilla** desde el menú de aplicaciones de Debian XFCE.
2. En la barra superior:
   - **Servidor:** `192.168.50.10`
   - **Nombre de usuario:** `ftpuser`
   - **Contraseña:** `Alumno123!`
   - **Puerto:** `21`
3. Haz clic en **Conexión rápida**.
4. Verás en el panel derecho (sitio remoto) únicamente la carpeta `/` que contiene `archivos/`.
5. Arrastra un archivo de tu cliente hacia la carpeta `archivos` y comprueba que la transferencia finaliza con éxito.
