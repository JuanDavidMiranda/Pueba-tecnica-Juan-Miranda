SIHOS - Censo de pacientes

Este proyecto corresponde a la prueba técnica. Consiste en una aplicación para gestionar el censo de pacientes, con funciones para registrar, consultar, buscar, editar y eliminar información desde una interfaz web, sin necesidad de recargar la página.

El proyecto cuenta con una API REST desarrollada en PHP, una base de datos MySQL y autenticación mediante JWT. También incluye las consultas SQL y las respuestas de las demás secciones de la prueba.


FUNCIONALIDADES:

Registro, consulta, edición y eliminación de pacientes.

Búsqueda por nombre, número de documento o correo electrónico.

Listado de pacientes con paginación.

Inicio y cierre de sesión con autenticación JWT.

Validación de los datos tanto en el navegador como en el servidor.

Carga opcional de fotografías en formato JPG, PNG o WEBP, con un tamaño máximo de 2 MB.

Catálogos de departamentos, municipios, tipos de documento y géneros.

Pruebas automatizadas con PHPUnit para validar diferentes partes de la aplicación.

Consultas SQL de la sección de productos en database/sql/productos.sql.

Las respuestas de las secciones 1 y 2 de la prueba se encuentran en docs/respuestas.md.



REQUISITOS:

Para ejecutar el proyecto:

PHP 8.2 o superior.

Composer.

MySQL o MariaDB.

Las extensiones de PHP pdo_mysql, mbstring y fileinfo.



INSTALACIÓN Y EJECUCIÓN:

1. Crear la base de datos

Ejecuta las siguientes instrucciones en MySQL. Si ya tienes un usuario para la aplicación, puedes utilizarlo y ajustar la configuración según corresponda.

CREATE DATABASE sihos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER 'sihos'@'127.0.0.1'
IDENTIFIED BY 'sihos_local';

GRANT ALL PRIVILEGES ON sihos.*
TO 'sihos'@'127.0.0.1';

FLUSH PRIVILEGES;

2. Instalar las dependencias

Desde la carpeta principal del proyecto, ejecuta:

composer install

3. Configurar el entorno

Copia el archivo de configuración de ejemplo:

cp .env.example .env

Si es necesario, modifica las credenciales de la base de datos en .env para que coincidan con tu configuración local.

4. Preparar la base de datos

Ejecuta el siguiente comando para aplicar las migraciones y cargar los datos iniciales:

php bin/setup.php

5. Iniciar la aplicación

composer serve

Abre la siguiente dirección en el navegador:

http://127.0.0.1:8741




USUARIO DE PR¿UEBA:

Para ingresar a la aplicación, utiliza las siguientes credenciales:

Correo: admin@sihos.local

Contraseña: 1234567890



PRUEBAS:

Para ejecutar las pruebas del proyecto, necesitas crear también una base de datos llamada sihos_test y otorgar permisos al usuario configurado.

Luego ejecuta:

composer test




ESTRUCTURA DEL PROYECTO:

public/
  index.php                 Punto de entrada de la aplicación
  assets/                   Estilos y scripts del navegador

src/
  Bootstrap.php             Configuración inicial y base de datos
  Http/
    Kernel.php              Registro de rutas y dependencias
    Middleware/             Validación de autenticación JWT
  Controller/               Controladores de la API
  Presenter/                Preparación de respuestas de pacientes
  Repository/               Consultas a la base de datos con PDO
  Validation/               Validaciones de pacientes
  Security/                 JWT y manejo de fotografías

database/
  migrations/               Estructura de las tablas
  sql/
    productos.sql           Consultas SQL de la prueba

views/                      Vistas y componentes de la interfaz
tests/                      Pruebas automatizadas con PHPUnit

docs/
  respuestas.md             Respuestas escritas de la prueba
