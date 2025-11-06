# Sistema de Gestión Educativa - INCES

Este es un sistema web completo para la gestión de cursos, estudiantes, docentes y certificados del Instituto Nacional de Capacitación y Educación Socialista (INCES).

## Tecnologías Utilizadas

- **Backend:** PHP 8.2+
- **Base de Datos:** MySQL 8.0+
- **Frontend:** Bootstrap 5.2+, Font Awesome 6.0+
- **Generación de PDF:** FPDF

## Características Principales

- **Autenticación Segura:** Sistema de login con roles (Administrador, Coordinador, Instructor).
- **Gestión Completa:** Módulos CRUD para Usuarios, Cursos, Docentes y Alumnos.
- **Inscripciones:** Funcionalidad para inscribir alumnos en múltiples cursos.
- **Generación de Certificados:** Emisión de certificados en PDF para cursos completados.
- **Auditoría:** Registro detallado de las acciones importantes realizadas en el sistema.
- **Reportes:** Visualización de informes, como el listado de alumnos por curso.

## Instalación

Siga estos pasos para instalar y ejecutar el sistema en un entorno local como XAMPP.

### 1. Requisitos Previos

- Tener instalado un servidor web local compatible con PHP 8.2+ y MySQL 8.0+ (se recomienda [XAMPP](https://www.apachefriends.org/index.html)).
- Un gestor de base de datos como phpMyAdmin (incluido en XAMPP).

### 2. Clonar o Descargar el Repositorio

Coloque la carpeta `sistema-inces` dentro del directorio `htdocs` de su instalación de XAMPP.

### 3. Crear la Base de Datos

1.  Inicie los servicios de Apache y MySQL en XAMPP.
2.  Abra phpMyAdmin en su navegador (generalmente en `http://localhost/phpmyadmin`).
3.  Cree una nueva base de datos llamada `inces_db`.
4.  Seleccione la base de datos `inces_db` y vaya a la pestaña **Importar**.
5.  Haga clic en **Seleccionar archivo** y elija el archivo `database.sql` que se encuentra en la raíz del proyecto.
6.  Haga clic en **Continuar** para ejecutar el script. Esto creará todas las tablas necesarias e insertará los datos iniciales.

### 4. Configurar la Conexión

- El archivo de configuración de la base de datos se encuentra en `sistema-inces/configuraciones/bd.php`.
- Por defecto, está configurado para un entorno XAMPP estándar:
  - **Usuario:** `root`
  - **Contraseña:** (vacía)
  - **Host:** `localhost`
  - **Nombre de la BD:** `inces_db`
- Si su configuración es diferente, actualice las constantes `DB_USER`, `DB_PASS`, etc., en este archivo.

### 5. Acceder al Sistema

- Abra su navegador y vaya a `http://localhost/sistema-inces/`.
- Será redirigido a la página de inicio de sesión.

## Credenciales por Defecto

- **Usuario:** `admin@inces.gob.ve`
- **Contraseña:** `password`

Se recomienda cambiar esta contraseña después del primer inicio de sesión.

## Estructura del Proyecto

```
sistema-inces/
├── index.php             # Punto de entrada y login
├── logout.php            # Cierre de sesión
├── database.sql          # Script de la base de datos
├── configuraciones/
│   ├── bd.php            # Conexión a la base de datos
│   └── auditoria.php     # Lógica de auditoría
├── secciones/
│   ├── dashboard.php     # Página principal
│   ├── auth_middleware.php # Verificador de sesión
│   ├── vista_*.php       # Vistas para cada módulo (CRUDs)
│   ├── certificado.php   # Generador de PDF
│   └── ajax_handler.php  # Endpoint para llamadas AJAX
├── templates/
│   ├── cabecera.php      # Plantilla de cabecera y menú
│   └── pie.php           # Plantilla de pie de página
└── librerias/
    └── fpdf/             # Librería FPDF
```
