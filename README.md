# laravel-clean

CRUD de **clientes** (`clients`) en Laravel 12 con **Clean Architecture**, dividido en cuatro capas: **Domain**, **Application**, **Infrastructure** y **Presentation**.

Es el gemelo de `laravel-mvc`: misma tabla, mismas reglas de negocio y misma interfaz, pero con la lógica separada del framework. Úsalo para comparar ambas organizaciones.

## Requisitos

- PHP 8.2 o superior (probado con 8.3, extensión `pdo_pgsql` habilitada)
- Composer
- Docker y Docker Compose v2

## Inicio rápido

```bash
git clone <url-del-repositorio> laravel-clean
cd laravel-clean

cp .env.example .env          # solo la primera vez
composer install
php artisan key:generate      # solo si APP_KEY está vacío

docker compose up -d          # levanta PostgreSQL (ver "Base de datos")
php artisan migrate           # si laravel-mvc ya migró, indicará que no hay nada pendiente

php artisan serve --port=8002
```

Abre http://localhost:8002/clients.

## Base de datos

La base PostgreSQL está definida en `docker-compose.yml` de este proyecto. **`laravel-mvc` declara el mismo contenedor**, así que ambos proyectos usan una sola base, `laravel_shared`.

- El archivo usa `name: laravel-shared-db`. Compose trata los dos proyectos como uno solo: levantar uno u otro reutiliza el mismo contenedor `laravel_pg`.
- Los valores salen de `.env`. Los dos `.env` deben tener las mismas credenciales de base de datos; si no coinciden, Compose recrea el contenedor.
- Los valores por defecto (`secret`, etc.) son solo para desarrollo local. **No los reutilices en otro entorno.**

| Parámetro | Valor de desarrollo |
|-----------|---------------------|
| Host | `127.0.0.1` |
| Puerto | `5432` |
| Base | `laravel_shared` |
| Usuario | `laravel` |
| Contraseña | `secret` |

Comandos útiles:

```bash
docker compose ps                      # estado del contenedor
docker compose logs -f postgres        # logs
docker compose down                    # detiene (conserva los datos)
docker compose down -v                 # detiene y BORRA los datos
docker exec -it laravel_pg psql -U laravel -d laravel_shared   # consola SQL
```

> **Importante:** la migración `2026_10_05_000000_create_clients_table.php` debe ser idéntica a la de `laravel-mvc`, porque ambos proyectos comparten la tabla `migrations`.

## Rutas

Las mismas que en `laravel-mvc`, pero apuntando a `App\Presentation\Http\Controllers\ClientController`:

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| GET | `/clients?page=N` | `clients.index` | Listado paginado (10 por página, más recientes primero) |
| GET | `/clients/create` | `clients.create` | Formulario de alta |
| POST | `/clients` | `clients.store` | Crear cliente |
| GET | `/clients/{client}` | `clients.show` | Detalle |
| GET | `/clients/{client}/edit` | `clients.edit` | Formulario de edición |
| PUT/PATCH | `/clients/{client}` | `clients.update` | Actualizar cliente |
| DELETE | `/clients/{client}` | `clients.destroy` | Eliminar (soft delete) |

Aquí `{client}` es el **id numérico**, no un modelo: el controlador recibe `int $client` y el caso de uso busca la entidad.

## Arquitectura

### Capas y dependencias

```
Presentation ──► Application ──► Domain ◄── Infrastructure
```

- **Domain** no depende de nada: PHP puro, sin Eloquent ni helpers de Laravel.
- **Application** depende solo de Domain.
- **Infrastructure** implementa los contratos de Domain.
- **Presentation** depende de Application. Nunca conoce Eloquent.

### Estructura

```
app/
├── Domain/
│   ├── Client/
│   │   ├── Entities/Client.php                     # entidad con reglas de negocio
│   │   ├── Repositories/ClientRepositoryInterface.php  # contrato de persistencia
│   │   └── Exceptions/
│   │       ├── ClientNotFoundException.php
│   │       └── DuplicateEmailException.php
│   └── Shared/Pagination/PaginatedResult.php       # DTO de página (genérico, sin Laravel)
├── Application/Client/
│   ├── DTOs/ClientData.php                         # datos de entrada inmutables
│   └── UseCases/
│       ├── ListClients.php
│       ├── GetClient.php
│       ├── CreateClient.php
│       ├── UpdateClient.php
│       └── DeleteClient.php
├── Infrastructure/
│   ├── Persistence/Eloquent/
│   │   ├── ClientModel.php                         # modelo Eloquent (tabla clients)
│   │   └── EloquentClientRepository.php            # implementa la interfaz
│   └── Providers/ClientServiceProvider.php         # bind interfaz → implementación
└── Presentation/Http/
    ├── Controllers/ClientController.php            # HTTP → caso de uso → vista
    └── Requests/
        ├── StoreClientRequest.php                  # validación de formato
        └── UpdateClientRequest.php
resources/views/clients/                            # vistas Blade
tests/
├── Feature/ClientTest.php                          # flujo HTTP completo
├── Unit/Application/ClientUseCasesTest.php         # casos de uso sin base de datos
└── Support/InMemoryClientRepository.php            # repositorio en memoria para tests
docker-compose.yml                                  # PostgreSQL compartido
```

### Flujo de una petición

Ejemplo: `POST /clients`

1. `StoreClientRequest` valida el formato (nombre, email, longitudes).
2. `ClientController::store` construye un `ClientData` con los datos validados.
3. `CreateClient` crea la entidad `Client` (normaliza nombre y email), comprueba que el email no exista con `findByEmail` y llama a `save`.
4. `EloquentClientRepository` traduce la entidad a `ClientModel` y la guarda.
5. Si hay duplicado, `DuplicateEmailException` vuelve al controlador, que regresa al formulario con el error.

### Reglas de negocio y dónde viven

| Regla | Ubicación |
|-------|-----------|
| Nombre no vacío, email en minúsculas y sin espacios | `Client` (entidad) |
| Email único (incluye clientes eliminados) | `CreateClient` / `UpdateClient` vía `findByEmail` |
| Cliente debe existir | `GetClient`, `UpdateClient`, `DeleteClient` → `ClientNotFoundException` → 404 |
| Formato del email y longitudes | Form Requests (Presentation) |

## Guía de estudio

Orden sugerido para entender la arquitectura de adentro hacia afuera: primero las reglas, al final el framework.

1. **`app/Domain/Client/Entities/Client.php`**: PHP puro. Ver `create()` frente a `reconstitute()` y por qué `normalizeEmail()` vive aquí.
2. **`app/Domain/Client/Repositories/ClientRepositoryInterface.php`**: el contrato que Domain necesita. No menciona Eloquent.
3. **`app/Application/Client/UseCases/CreateClient.php`**: la regla de email único. Leer sin buscar Laravel: solo usa el contrato.
4. **`app/Infrastructure/Persistence/Eloquent/EloquentClientRepository.php`**: la implementación concreta y el mapeo `toDomain()`.
5. **`app/Infrastructure/Providers/ClientServiceProvider.php`** y **`bootstrap/providers.php`**: dónde se conecta la interfaz con su implementación.
6. **`app/Presentation/Http/Controllers/ClientController.php`**: cada método traduce HTTP a un caso de uso y maneja las excepciones.
7. **`tests/Unit/Application/ClientUseCasesTest.php`**: los casos de uso probados sin base de datos. Comparar con `tests/Feature/ClientTest.php`.

Preguntas para discutir:
- ¿Qué pasaría si `CreateClient` consultara `ClientModel` directamente? ¿Qué test dejaría de poder escribirse sin base de datos?
- ¿Por qué `EloquentClientRepository::findByEmail()` incluye los registros eliminados?
- ¿Dónde pondrías una regla nueva, por ejemplo "un cliente no puede tener más de 3 teléfonos"?

Ejercicios propuestos:
- Agregar un caso de uso `SearchClients` por nombre, con su test unitario contra `InMemoryClientRepository`.
- Implementar un nuevo repositorio (por ejemplo, en memoria o en archivo) sin tocar los casos de uso. Eso demuestra la inversión de dependencias.
- Hacer que `UpdateClient` no permita cambiar el email de un cliente eliminado.

## Decisiones de diseño

- **Entidad separada del modelo Eloquent**: `Client` no extiende nada de Laravel. `EloquentClientRepository::toDomain()` hace el mapeo.
- **Constructores privados con fábricas**: `Client::create()` para registros nuevos y `Client::reconstitute()` para los que vienen de la base.
- **Id asignado después de guardar**: al crear, el repositorio asigna el id con `assignId()`. Al actualizar no se vuelve a asignar.
- **Email único en la aplicación**, no solo en la base: los Form Requests validan formato, y la unicidad la impone el caso de uso. Así la regla está en un solo lugar.
- **Búsqueda por email incluye registros eliminados** (`withTrashed`), igual que el índice único de PostgreSQL.
- **Paginación con DTO propio**: `ClientRepositoryInterface::paginate()` devuelve un `PaginatedResult` definido en Domain, no un `LengthAwarePaginator` de Laravel. El dominio sigue sin depender del framework. `EloquentClientRepository` hace la traducción y `InMemoryClientRepository` replica el orden y el corte para los tests.
- **Página saneada en la aplicación**: `ListClients` fuerza `page >= 1` y `perPage >= 1`, así que un `?page=0` o `?page=abc` cae en la primera página. El orden es el más reciente primero (`id` descendente).
- **Binding manual**: el controlador convierte `ClientNotFoundException` en `abort(404)` explícitamente, en lugar de usar route model binding.

## Tests

```bash
php artisan test
```

- **Unitarios** (`ClientUseCasesTest`): prueban los casos de uso contra `InMemoryClientRepository`. No necesitan base de datos y verifican normalización, duplicados, 404, borrado y paginación.
- **Feature** (`ClientTest`): prueban el flujo HTTP con SQLite en memoria (ver `phpunit.xml`), así que **no tocan la base compartida**.

## Estilo

```bash
vendor/bin/pint
```
