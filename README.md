# Sistema de Gestión de Certificados y Eventos (QR Simple)

Sistema web para la gestión integral de participantes, cursos y la generación automatizada de certificados con validación mediante código QR. Este proyecto permite administrar usuarios, eventos académicos y emitir constancias digitales verificables.

## Características Principales

*   **Gestión de Usuarios**:
    *   Administración de usuarios del sistema (Crear, Editar, Eliminar).
    *   Perfiles de usuario con carga de avatar personalizado.
    *   Roles y permisos básicos.

*   **Gestión de Cursos y Eventos**:
    *   Creación y edición de eventos académicos (Cursos, Diplomados, Webinars).
    *   Control de estados (Activo, Inactivo, Archivado).

*   **Gestión de Participantes**:
    *   Registro individual de participantes.
    *   **Carga Masiva**: Importación de participantes desde archivos CSV con detección inteligente de columnas (nombres, apellidos, email, dni, etc.).
    *   Matriculación automática en cursos específicos.
    *   Prevención de duplicados mediante validación de Email y DNI.

*   **Emisión de Certificados**:
    *   Generación de certificados en formato PDF (Librería TCPDF).
    *   Incrustación de código QR único para cada certificado.
    *   Soporte para plantillas de fondo personalizadas por curso.
    *   Descarga individual de certificados.

*   **Validación y Seguridad**:
    *   Módulo público de validación de certificados mediante escaneo de QR.
    *   Autenticación de usuarios segura.

*   **Interfaz de Usuario**:
    *   Panel administrativo moderno y responsivo basado en **AdminLTE 3**.
    *   Componentes interactivos con Bootstrap 4.

## Requisitos del Sistema

*   **Servidor Web**: Apache o Nginx.
*   **Lenguaje**: PHP 7.4 o superior.
*   **Base de Datos**: MySQL 5.7+ o MariaDB 10.x.
*   **Extensiones de PHP**:
    *   `pdo` y `pdo_mysql` (Para conexión a base de datos).
    *   `gd` (Para generación de códigos QR y manipulación de imágenes).
    *   `mbstring` (Para manejo de caracteres UTF-8).
    *   `json` (Para respuestas AJAX).

## Instalación

1.  **Clonar el repositorio** o descargar los archivos en tu directorio web (ej. `htdocs/qr_simple`).

2.  **Base de Datos**:
    *   Crear una base de datos MySQL (ej. `aulavirtual` o `qr_simple`).
    *   Importar el archivo SQL principal proporcionado (ej. `master_qr_simple.sql` o `aulavirtual.sql`).

3.  **Configuración**:
    *   Editar el archivo `config/database.php` para configurar las credenciales de conexión:
        ```php
        // Ejemplo de configuración en config/database.php
        $host = getenv('QR_DB_HOST') ?: 'localhost';
        $dbName = getenv('QR_DB_NAME') ?: 'aulavirtual';
        $user = getenv('QR_DB_USER') ?: 'root';
        $password = getenv('QR_DB_PASSWORD') ?: '';
        ```
    *   Alternativamente, puedes configurar las variables de entorno `QR_DB_HOST`, `QR_DB_NAME`, `QR_DB_USER` y `QR_DB_PASSWORD` en tu servidor.

4.  **Permisos**:
    *   Asegúrate de que las carpetas `images/plantilla/` y `lib/phpqrcode/cache/` (si existe) tengan permisos de escritura para el servidor web.

## Estructura del Proyecto

El proyecto sigue una arquitectura MVC (Modelo-Vista-Controlador) personalizada:

*   `assets/`: Archivos estáticos (CSS, JS, Imágenes, AdminLTE).
*   `config/`: Archivos de configuración del sistema.
*   `controllers/`: Controladores que manejan la lógica de negocio (ParticipantController, CertificateController, etc.).
*   `models/`: Modelos que interactúan con la base de datos.
*   `views/`: Vistas y plantillas HTML organizadas por módulo.
*   `lib/`: Librerías de terceros (TCPDF, PHPQRCode).
*   `index.php`: Punto de entrada único y enrutador de la aplicación.

## Uso Básico

1.  Accede al sistema a través de tu navegador (ej. `http://localhost/qr_simple`).
2.  Inicia sesión con tus credenciales de administrador.
3.  Utiliza el menú lateral para navegar entre **Usuarios**, **Cursos** y **Participantes**.
4.  Para cargar participantes masivamente:
    *   Ve a **Participantes** > **Nuevo Participante**.
    *   Selecciona la opción de carga masiva o descarga la plantilla CSV.
    *   Sube tu archivo y selecciona el curso destino.

## Créditos y Librerías

*   [AdminLTE 3](https://adminlte.io/) - Plantilla de panel administrativo open source.
*   [TCPDF](https://github.com/tecnickcom/TCPDF) - Librería PHP para generar documentos PDF.
*   [PHP QR Code](http://phpqrcode.sourceforge.net/) - Librería para generar códigos QR.
*   [Bootstrap 4](https://getbootstrap.com/) - Framework CSS.
