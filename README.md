# ⚽ DatosFutbol

Plataforma web integral para la gestión y seguimiento de torneos, ligas de fútbol, clubes, planteles, estadísticas avanzadas y resultados en tiempo real. Construida bajo una arquitectura **MVC limpia en PHP 8**, con persistencia en **MySQL**, estilos modernos con **Tailwind CSS v4** y un fuerte enfoque en **seguridad y rendimiento**.

---

## 🚀 Tecnologías Utilizadas

### Backend & Arquitectura
- **PHP 8.2+**: Código moderno con tipado estricto (`declare(strict_types=1)`), constructores promocionados y coincidencia de patrones (*match*).
- **Patrón MVC**: Separación estricta entre Modelos, Vistas y Controladores.
- **Enrutador Personalizado (`MVC\Router`)**: Despacho de rutas amigables (`GET`, `POST`) con soporte para vistas de servidor y endpoints de API JSON.
- **ActiveRecord sobre PDO**: Capa de persistencia desacoplada con consultas preparadas y protección nativa contra inyecciones SQL.
- **PHPMailer 7**: Envío de correos transaccionales seguros vía SMTP para invitaciones y recuperación de accesos.
- **PHP dotenv 5.8**: Gestión segura de variables de entorno fuera del código fuente.

### Frontend & Experiencia de Usuario
- **Tailwind CSS v4**: Framework de diseño utilitario, optimizado y compilado para máxima velocidad de carga.
- **JavaScript Vanilla (ES6+)**: Peticiones asíncronas con `fetch` para operaciones CRUD en tiempo real sin recarga de página.
- **Modo Claro / Oscuro**: Selector de tema persistente mediante `localStorage` y detección de preferencias del sistema.
- **Accesibilidad (a11y)**: HTML5 semántico, atributos ARIA, navegación por teclado y etiquetas accesibles para lectores de pantalla.

### Base de Datos & Concurrencia
- **MySQL 8.0+ / MariaDB**:
  - Motor **InnoDB** con codificación `utf8mb4` y cotejamiento `utf8mb4_unicode_ci` (soporte completo de tildes y caracteres especiales).
  - Integridad referencial estricta mediante claves foráneas (`CASCADE`, `SET NULL`).
  - Manejo de concurrencia y transacciones ACID con bloqueos `FOR UPDATE` en operaciones críticas (como el sorteo y generación de fixtures por algoritmo de Berger).

---

## 🛡️ Buenas Prácticas de Seguridad Implementadas

La plataforma fue diseñada e auditada siguiendo las directrices de seguridad de **OWASP**:

- **Protección contra Inyección SQL**: Todas las consultas a la base de datos utilizan sentencias preparadas con parámetros vinculados (`prepare` y `execute`). Los nombres de columnas dinámicas se validan contra listas blancas cerradas en los modelos (`columnaSegura`).
- **Autenticación Robusta**:
  - Contraseñas hasheadas mediante `password_hash()` con algoritmo seguro por defecto y comprobación de rehasheo automático (`password_needs_rehash`).
  - **Mitigación de Ataques de Temporización**: En inicios de sesión con usuarios inexistentes, se valida contra un hash señuelo (`HASH_FALSO`) para equiparar los tiempos de respuesta.
  - **Anti-Fuerza Bruta**: Limitador de tasa (*Rate Limiting*) que bloquea temporalmente accesos tras intentos fallidos consecutivos por IP y cuenta.
  - Regeneración de ID de sesión (`session_regenerate_id(true)`) al autenticar, previniendo *Session Fixation*.
  - Cookies con directivas `HttpOnly`, `SameSite=Lax` y `Secure` activado en conexiones HTTPS.
- **Protección contra CSRF**: Validación de tokens criptográficos aleatorios de 32 bytes (`hash_equals`) en todas las operaciones de modificación de estado (`POST`, `PUT`, `DELETE`).
- **Encabezados HTTP de Seguridad**:
  - **Content Security Policy (CSP)** con *Nonce* dinámico por petición para scripts autorizados.
  - **HTTP Strict Transport Security (HSTS)** para forzar conexiones cifradas.
  - `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN` y `Permissions-Policy`.
- **Procesamiento Seguro de Archivos**:
  - Verificación estricta de tipo MIME mediante `finfo`.
  - **Re-codificación obligatoria con GD**: Toda imagen subida es re-dibujada desde cero, eliminando cualquier código malicioso camuflado en metadatos EXIF.
  - Prevención de *Path Traversal* y nombres aleatorios en hashes hexadecimales.
  - Bloqueo de ejecución de scripts en el directorio de subidas (`uploads/.htaccess`).
- **Protección contra XSS**: Escapado contextual de salidas en vistas mediante `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` y casteo estricto de tipos de datos.

---

## 📂 Estructura del Proyecto

```text
datosfutbol_php/
├── bin/                   # Scripts de consola CLI (creación de administrador inicial)
├── classes/               # Servicios de soporte (Autenticación, Notificaciones por Email)
├── controllers/           # Controladores públicos, administrativos y APIs JSON
├── includes/              # Arranque de la app, cabeceras HTTP, helpers y conexión a BD
├── models/                # Entidades del dominio y capa ActiveRecord
├── public/                # Raíz web pública (Único punto de acceso permitido en producción)
│   ├── css/               # Hojas de estilo compiladas
│   ├── js/                # Scripts del cliente (CRUD, alineaciones, partidos, temas)
│   ├── uploads/           # Directorio de imágenes procesadas (con .htaccess de seguridad)
│   ├── .htaccess          # Reglas de reescritura para servidores Apache / LiteSpeed
│   └── index.php          # Front Controller (recibe y despacha todas las peticiones)
├── src/                   # Fuentes de estilos antes de compilar con Tailwind
├── views/                 # Plantillas PHP divididas en layouts, público, admin y auth
├── .env.example           # Plantilla de variables de entorno
├── .gitignore             # Exclusiones de Git (credenciales, dependencias, volcados SQL)
├── composer.json          # Dependencias y autoloading PSR-4 de PHP
├── package.json           # Dependencias y scripts de compilación de Tailwind CSS
└── schema.sql             # Estructura limpia y reproducible de la base de datos
```

---

## ⚙️ Instalación y Puesta en Marcha (Entorno Local)

### 1. Clonar el repositorio
```bash
git clone https://github.com/Sebas-1977/datosfutbol.git
cd datosfutbol
```

### 2. Instalar dependencias
```bash
# Dependencias de PHP
composer install

# Dependencias de Frontend
npm install
```

### 3. Configurar Variables de Entorno
Copia la plantilla y configura tus credenciales locales:
```bash
cp .env.example .env
```
Edita el archivo `.env`:
```ini
DB_HOST=localhost
DB_NAME=datosfutbol
DB_USER=root
DB_PASS=
DB_PORT=3306

APP_URL=http://localhost:3000

# Credenciales SMTP (ej. Mailtrap o servidor propio)
EMAIL_HOST=sandbox.smtp.mailtrap.io
EMAIL_PORT=2525
EMAIL_USER=tu_usuario
EMAIL_PASS=tu_password
EMAIL_FROM=contacto@datosfutbol.test
EMAIL_NAME="DatosFutbol"
```

### 4. Base de Datos
1. Crea una base de datos en MySQL llamada `datosfutbol` con cotejamiento `utf8mb4_unicode_ci`.
2. Importa el archivo `schema.sql`:
   ```bash
   mysql -u root -p datosfutbol < schema.sql
   ```

### 5. Crear el Usuario Administrador Inicial
Ejecuta el script interactivo desde tu terminal:
```bash
php bin/crear-admin.php
```
Ingresa nombre, apellido, correo electrónico y contraseña.

### 6. Compilar Estilos y Levantar Servidor
En una terminal:
```bash
# Compilar Tailwind CSS (modo desarrollo con escucha de cambios)
npm run dev
```

En otra terminal, levanta el servidor local apuntando a la raíz pública:
```bash
php -S localhost:3000 -t public
```

Visita `http://localhost:3000` en tu navegador. Para ingresar al panel de administración, accede a `http://localhost:3000/login`.

---

## 🌐 Despliegue en Producción

Para subir el proyecto a un servidor público (cPanel, VPS con Apache/Nginx o Cloud):

1. **Raíz Web Obligatoria**: Configura el `DocumentRoot` del servidor web apuntando **exclusivamente a la carpeta `public/`**. El código fuente y el archivo `.env` deben quedar un nivel por encima, fuera del alcance web directo.
2. **Optimizar dependencias**:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   ```
3. **Permisos de escritura**:
   Asegura que el usuario del servidor web tenga permisos de escritura en la carpeta de subidas:
   ```bash
   chmod -R 775 public/uploads
   ```
4. **Certificado SSL**:
   Configura un certificado HTTPS (Let's Encrypt / Certbot) para habilitar HSTS y transmisión segura de cookies de sesión.

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia **MIT**. Desarrollado por [Sebastián Rodríguez](https://github.com/Sebas-1977).
