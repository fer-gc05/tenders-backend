# Suplos

API de licitaciones construida en PHP puro, con `illuminate/database` y `illuminate/events` como componentes y Eloquent como ORM. Sigue una arquitectura MVC sin frameworks ni micro-frameworks.

Todas las respuestas de la API (JSON y mensajes de error) se devuelven en inglés.

## Requisitos

- PHP 8.3 o superior
- Composer 2.x
- MySQL o MariaDB con la extensión `pdo_mysql`

## Instalación

```bash
composer install
cp .env.example .env
```

Edita `.env` con las credenciales de tu base de datos y crea la base de datos `suplos`.

## Ejecución

```bash
composer dev
```

La aplicación queda disponible en `http://localhost:8000`. Puedes comprobar el estado de la API en `http://localhost:8000/api/v1/status`.

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
routes/
└── api.php                   Definición de rutas de la API
public/
├── index.php                 Punto de entrada (bootstrap)
└── router.php                Router del servidor embebido (desarrollo)
```

El flujo MVC es `Request → Router → Controller → Model → View → Response`.

Las carpetas `Models` y `Views` quedan preparadas para su implementación. Las clases de `app/Http` y `app/Support` implementan la capa HTTP: el enrutador soporta rutas con parámetros (`{id}`), closures o `[Controller::class, 'metodo']`, y responde 404/405 en JSON.

## Validación

La validación se define en clases que extienden `App\Http\FormRequest`. El Router las detecta por el type-hint del controlador y valida **antes** de ejecutarlo; si falla, responde `422` con los errores en inglés.

```php
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ];
    }
}
```

```php
class LoginController extends Controller
{
    public function login(LoginRequest $request): Response
    {
        $credentials = $request->validated();  // solo los campos de rules()
        // ...
    }
}
```

Respuesta de error:

```json
{
    "error": "Validation error.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

Reglas disponibles: `required`, `nullable`, `string`, `email`, `integer`, `numeric`, `boolean`, `array`, `min`, `max`, `date`, `in:val1,val2`.

## Variables de entorno

| Variable | Descripción | Valor por defecto |
| --- | --- | --- |
| `APP_NAME` | Nombre de la aplicación | `Suplos` |
| `APP_ENV` | Entorno de ejecución | `local` |
| `DB_CONNECTION` | Driver de base de datos | `mysql` |
| `DB_HOST` | Host de la base de datos | `127.0.0.1` |
| `DB_PORT` | Puerto de la base de datos | `3306` |
| `DB_DATABASE` | Nombre de la base de datos | `suplos` |
| `DB_USERNAME` | Usuario de la base de datos | `root` |
| `DB_PASSWORD` | Contraseña de la base de datos | vacío |
