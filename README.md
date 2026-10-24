# Tenders Backend

API de licitaciones construida en PHP puro, con `illuminate/database` y `illuminate/events` como componentes y Eloquent como ORM. Sigue una arquitectura MVC sin frameworks ni micro-frameworks.

Todas las respuestas de la API (JSON y mensajes de error) se devuelven en inglés.

## Requisitos

- PHP 8.3 o superior (probado en PHP 8.5)
- Composer 2.x
- MariaDB o MySQL con la extensión `pdo_mysql`

## Instalación

```bash
git clone https://github.com/fer-gc05/tenders-backend.git
cd tenders-backend
composer install
cp .env.example .env
```

Edita `.env` con las credenciales de tu base de datos. Los valores por defecto apuntan a MariaDB en `127.0.0.1:3306`, base `tenders` y usuario `root`; ajústalos a tu instalación.

## Base de datos

El esquema y el diagrama se conservan en `docs/db/`:

| Archivo | Contenido |
| --- | --- |
| [`docs/db/schema.sql`](docs/db/schema.sql) | Script de construcción de la base de datos |
| [`docs/db/tenders.png`](docs/db/tenders.png) | Diagrama entidad-relación |

![Diagrama de la base de datos](docs/db/tenders.png)

Para crear la base de datos y sus tablas, carga el script:

```bash
mariadb -u root -p < docs/db/schema.sql
```

El script crea la base de datos con `create database if not exists`, pero las tablas se crean sin esa salvaguarda: al reejecutarlo sobre una base ya poblada, MySQL/MariaDB responderá `Table 'activities' already exists`. Para reconstruir el esquema desde cero, elimina primero la base de datos:

```bash
mariadb -u root -p -e "DROP DATABASE IF EXISTS tenders;"
mariadb -u root -p < docs/db/schema.sql
```

### Tablas

| Tabla | Contenido |
| --- | --- |
| `activities` | Catálogo de productos y servicios que se pueden licitar, clasificados por segmento, familia y clase. |
| `tenders` | Licitaciones: código consecutivo, objeto, presupuesto, moneda, fechas de apertura y cierre, estado y actividad asociada. |
| `tender_documents` | Documentos adjuntos a cada licitación (título y ruta del archivo en el servidor). |

### Relaciones

- `tenders.activity_id` → `activities.id`
- `tender_documents.tender_id` → `tenders.id`

Las tres tablas registran `created_at` y `updated_at` de forma automática (`datetime` con `current_timestamp`).

## Ejecución

### Servidor de desarrollo

```bash
composer dev
```

Levanta el servidor embebido de PHP en `http://localhost:8000` con `public/router.php` como router, de modo que todas las rutas de la API (y no solo `/index.php`) llegan al front controller.

```bash
curl http://localhost:8000/api/v1/status
# {"app":"tenders_backend","status":"ok"}
```

## Endpoints

| Método | Ruta | Descripción | Respuesta |
| --- | --- | --- | --- |
| `GET` | `/api/v1/status` | Estado del servicio. | `200` `{"app":"tenders_backend","status":"ok"}` |

Las rutas inexistentes responden `404` y las peticiones con un método no permitido responden `405`, siempre en JSON. El `405` incluye el header `Allow` con los métodos registrados para la ruta (por ejemplo, `Allow: GET`), como exige el estándar HTTP.

El CRUD de licitaciones está en desarrollo; por ahora el único endpoint publicado es el de estado.

## Estructura

```
app/
├── Config/
│   └── Database.php          Inicialización de la conexión y Eloquent
├── Controllers/
├── Http/
│   ├── Controller.php        Controlador base
│   ├── FormRequest.php       Solicitud con validación declarativa
│   ├── Request.php           Solicitud HTTP
│   ├── Response.php          Respuesta HTTP
│   ├── Router.php            Enrutador
│   └── Requests/             Validaciones por solicitud
├── Models/
├── Support/
│   ├── ErrorHandler.php      Manejo global de errores y excepciones
│   ├── ValidationException.php
│   └── Validator.php         Motor de reglas de validación
└── Views/                    Representación JSON de los recursos
config/
└── Database.php              Configuración de la base de datos
docs/
└── db/
    ├── schema.sql            Construcción de la base de datos
    └── tenders.png           Diagrama entidad-relación
routes/
└── api.php                   Definición de rutas de la API
public/
├── index.php                 Punto de entrada (bootstrap)
└── router.php                Router del servidor embebido (desarrollo)
```

El flujo MVC es `Request → Router → Controller → Model → View → Response`.

Las carpetas `Models` y `Views` quedan preparadas para su implementación. Las clases de `app/Http` y `app/Support` implementan la capa HTTP: el enrutador soporta rutas con parámetros (`{id}`), closures o `[Controller::class, 'metodo']`, y responde 404/405 en JSON.

### Convención de documentación

Las clases nuevas o modificadas llevan un encabezado que registra autoría, versión, fecha de creación, fecha de última actualización y espacio de nombres. Cada modificación añade además su entrada en el historial `@change`:

```php
/**
 * Realiza la validación de la solicitud de autenticación mTLS y parámetros de red.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   App\Http\Requests
 *
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Ajuste de reglas de validación.
 */
```

| Etiqueta | Regla |
| --- | --- |
| `@author` | Autoría del código. No cambia. |
| `@version` | Versión semántica de la clase (`MAJOR.MINOR.PATCH`). Se incrementa en cada modificación. |
| `@since` | Fecha real de creación del archivo. No cambia. |
| `@updated` | Fecha de la última modificación. En la creación coincide con `@since`. |
| `@package` | Espacio de nombres al que pertenece la clase. |
| `@change` | Historial de cambios, con el formato `versión - fecha - autor - descripción`. Una línea por versión, la más reciente primero. |

La versión sigue el criterio habitual: *patch* para ajustes internos, *minor* cuando se incorpora comportamiento nuevo y *major* cuando el cambio rompe la compatibilidad de la clase. Las clases que ya existían antes de esta convención conservan su encabezado original (`@author`, `@version`, `@since`, `@package`) hasta que se modifiquen.

### Manejo de errores

`App\Support\ErrorHandler` registra los manejadores global de PHP y convierte cualquier excepción en una respuesta JSON:

| Situación | Código | Cuerpo |
| --- | --- | --- |
| Error de validación | `422` | `{"error":"Validation error.","errors":{...}}` |
| Excepción no controlada | `500` | `{"error":"Internal server error."}` |

Con `APP_ENV=local` la respuesta `500` incluye el mensaje original, el archivo y la traza, para facilitar el diagnóstico. Los avisos (`E_NOTICE`) y las deprecaciones (`E_DEPRECATED`) no interrumpen la petición: se escriben en el log del servicio (`journalctl -u php-fpm`); solo las advertencias y los errores reales se convierten en `500`.

## Validación

La validación se define en clases que extienden `App\Http\FormRequest`. El Router las detecta por el type-hint del controlador y valida **antes** de ejecutarlo; si falla, responde `422` con los errores en inglés.

Cada solicitud vive en `app/Http/Requests/` y sus reglas se corresponden con las columnas de la tabla que recibe. Ejemplo para la creación de una licitación:

`app/Http/Requests/StoreTenderRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Http\FormRequest;

class StoreTenderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'consecutive_code' => 'required|string|max:50',
            'subject' => 'required|string|max:150',
            'description' => 'required|string|max:400',
            'currency' => 'required|string|max:3|in:COP,USD,EUR',
            'budget' => 'required|numeric',
            'activity_id' => 'required|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'close_date' => 'required|date',
            'close_time' => 'required|string',
            'status' => 'nullable|string|in:draft,published,closed',
        ];
    }
}
```

`app/Controllers/Api/V1/TenderController.php`

```php
<?php

namespace App\Controllers\Api\V1;

use App\Http\Controller;
use App\Http\Requests\StoreTenderRequest;
use App\Http\Response;

class TenderController extends Controller
{
    public function store(StoreTenderRequest $request): Response
    {
        $data = $request->validated();  // solo los campos de rules()
        // ...
    }
}
```

El controlador recibe la solicitud ya validada: si alguna regla falla, el Router interrumpe el flujo y responde `422` sin llegar a ejecutar el método.

Las reglas se ajustan al esquema de `tenders`: `max` respeta la longitud de la columna, `in` acota los valores permitidos y `nullable` acepta que el campo no llegue, de modo que la base de datos aplique su valor por defecto (`draft` en el caso de `status`). `min` y `max` interpretan el valor según su tipo: un array se compara por la cantidad de elementos, un valor numérico (incluida una cadena como `"15000000.00"`) por su valor y cualquier otra cadena por su longitud; así, el presupuesto se acota con `numeric|min:0` y los textos con `max:<longitud de la columna>`. La regla `date` exige el formato ISO `YYYY-MM-DD` (rechaza `"next monday"` o el 31 de febrero) y `time` exige `HH:MM` o `HH:MM:SS` entre `00:00:00` y `23:59:59`.

Respuesta de error:

```json
{
    "error": "Validation error.",
    "errors": {
        "subject": ["The subject field is required."]
    }
}
```

Reglas disponibles: `required`, `nullable`, `string`, `email`, `integer`, `numeric`, `boolean`, `array`, `min:<n>`, `max:<n>`, `date`, `time`, `in:val1,val2`.

Las validaciones que las reglas no expresan (por ejemplo, que una clave foránea exista o que un código sea único) se agregan en el hook `after()` de cada `FormRequest`, que se ejecuta solo si las reglas pasan y devuelve un mapa `campo => mensajes` para responder `422`:

```php
protected function after(): array
{
    return \App\Models\Activity::find($this->body('activity_id'))
        ? []
        : ['activity_id' => ['The selected activity is invalid.']];
}
```

## Variables de entorno

| Variable | Descripción | Valor por defecto |
| --- | --- | --- |
| `APP_NAME` | Nombre de la aplicación | `tenders_backend` |
| `APP_ENV` | Entorno de ejecución. En `local`, los errores 500 incluyen mensaje, archivo y traza | `local` |
| `APP_TIMEZONE` | Zona horaria de PHP. Debe coincidir con la de la base de datos | `America/Bogota` |
| `DB_CONNECTION` | Driver de base de datos | `mariadb` |
| `DB_HOST` | Host de la base de datos | `127.0.0.1` |
| `DB_PORT` | Puerto de la base de datos | `3306` |
| `DB_DATABASE` | Nombre de la base de datos | `tenders` |
| `DB_USERNAME` | Usuario de la base de datos | `root` |
| `DB_PASSWORD` | Contraseña de la base de datos | vacío |
