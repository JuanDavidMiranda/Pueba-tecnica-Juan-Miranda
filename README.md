# SIHOS · Censo de pacientes

Prueba técnica EST-SEM / SIHOS. Incluye las consultas SQL de productos, las respuestas de programación y una aplicación para el censo de pacientes: API REST en PHP, MySQL con PDO, autenticación JWT y una interfaz que crea, lista, busca, edita y elimina sin recargar la página.

Antes de entregar, escriba su nombre y cédula al inicio de [docs/respuestas.md](docs/respuestas.md).

## Qué cubre

- Migraciones y datos de prueba: 5 departamentos, 2 municipios por departamento, 2 tipos de documento, géneros, el administrador y 5 pacientes.
- `UserController` con inicio de sesión, sesión actual y cierre. El CRUD del paciente está en `PacienteController` para no mezclar autenticación con el censo.
- Validación de servidor y de navegador, sentencias preparadas y fotografías opcionales (JPG, PNG o WEBP, máximo 2 MB).
- Paginación y búsqueda por nombre, documento o correo.
- Pruebas PHPUnit del token, el validador, la carga de imágenes, el controlador de usuario y el flujo HTTP del paciente.

Las respuestas escritas de las secciones 1 y 2 están en [docs/respuestas.md](docs/respuestas.md). El SQL se puede volver a ejecutar con `database/sql/productos.sql`.

## Requisitos

- PHP 8.2 o superior, con `pdo_mysql`, `mbstring` y `fileinfo`
- Composer
- MySQL o MariaDB

## Puesta en marcha

Cree la base y el usuario (ajuste la contraseña si no usa la de desarrollo):

```sql
CREATE DATABASE sihos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sihos'@'127.0.0.1' IDENTIFIED BY 'sihos_local';
GRANT ALL PRIVILEGES ON sihos.* TO 'sihos'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Luego:

```bash
composer install
cp .env.example .env
php bin/setup.php
composer serve
```

Abra http://127.0.0.1:8741

Si no copia `.env`, la aplicación usa `.env.example`. El documento raíz del servidor debe ser `public/`, que es lo que hace `composer serve`. Así `.env` no queda expuesto.

Acceso de la prueba:

- Correo: `admin@sihos.local`
- Contraseña: `1234567890`

La contraseña se guarda con `password_hash`. No está en texto plano.

Para las pruebas automáticas hace falta otra base, `sihos_test`, con permiso para el mismo usuario. PHPUnit la usa sola y no borra los pacientes de `sihos`.

```bash
composer test
```

## API

Todas las rutas, excepto el login, piden `Authorization: Bearer <token>`.

| Método | Ruta | Uso |
| --- | --- | --- |
| POST | `/api/auth/login` | Devuelve el JWT |
| GET | `/api/auth/me` | Usuario de la sesión |
| POST | `/api/auth/logout` | Confirma el cierre; el cliente borra el token |
| GET | `/api/pacientes?q=&page=&per_page=` | Listado paginado |
| GET | `/api/pacientes/{id}` | Detalle |
| POST | `/api/pacientes` | Alta, JSON o formulario con foto |
| PUT o POST | `/api/pacientes/{id}` | Edición |
| DELETE | `/api/pacientes/{id}` | Eliminación |
| GET | `/api/departamentos` | Catálogo |
| GET | `/api/municipios?departamento_id=` | Municipios del departamento |
| GET | `/api/tipos-documento` | Catálogo |
| GET | `/api/generos` | Catálogo |

Un error de validación responde `422` con `message` y `errors`. Un token ausente o vencido responde `401`.

## Estructura

```
public/index.php              Front controller
src/Bootstrap.php             Entorno y preparación de la base
src/Http/Kernel.php           Dependencias y rutas
src/Http/Middleware           Autenticación JWT
src/Controller                UserController, PacienteController, CatalogController
src/Presenter                 Respuesta pública del paciente
src/Repository                PDO y sentencias preparadas
src/Validation                Reglas del paciente
src/Security                  JWT HS256 y fotografías
database/migrations           Tablas del censo
database/sql/productos.sql    Sección SQL de la prueba
views/                        Página y parciales
public/assets/                Estilos y comportamiento del navegador
tests/                        PHPUnit
```

La fotografía, `created_at` y `updated_at` se agregan a `paciente` porque el enunciado pide la foto como dato opcional y conviene saber cuándo cambió el registro. El resto de columnas sigue el enunciado.

`002_municipio_departamento.sql` impide guardar un municipio de otro departamento. `schema_migrations` registra lo ya aplicado, así el DDL no se repite en cada petición. El hash del administrador solo se calcula al crear la cuenta o al reponerla en las pruebas.
