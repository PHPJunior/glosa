# Glosa - Laravel Translation Management System

A standalone Laravel Translation Management System package. Managed your translations with a beautiful UI.

## Installation

You can install the package via composer:

```bash
composer require php-junior/glosa
```

## Configuration

Publish the config file with:

```bash
php artisan vendor:publish --tag="glosa-config"
php artisan migrate
```

This will create a `config/glosa.php` file in your app, where you can configure the route prefix, middleware, and API settings.

### Route Prefix

By default, the UI is accessible at `/glosa`. You can change this in the config:

```php
'route_prefix' => 'translations',
```

### Authorization

Glosa exposes a dashboard at `/glosa`. By default, this is only accessible in the `local` environment. To allow access in other environments, you must define the `viewGlosa` gate in your `AppServiceProvider` (or `AuthServiceProvider`).

```php
use Illuminate\Support\Facades\Gate;

public function boot()
{
    Gate::define('viewGlosa', function ($user) {
        return in_array($user->email, ['admin@app.com']);
    });
}
```

## Features

### Public API

Glosa provides a public API endpoint to fetch translations for a specific locale. This is useful for Single Page Applications (SPAs) or mobile apps.

**Endpoint:** `GET /api/translations/{locale}`

**Configuration:**

You can enable/disable this feature and control the response format in `config/glosa.php` or your `.env` file:

```dotenv
GLOSA_ENABLE_PUBLIC_API=true
GLOSA_PUBLIC_API_URL=api/translations/{locale}
GLOSA_PUBLIC_API_NESTED=true
```

-   `GLOSA_ENABLE_PUBLIC_API`: Set to `true` to enable the endpoint.
-   `GLOSA_PUBLIC_API_URL`: Customize the endpoint URL. Must include `{locale}`.
-   `GLOSA_PUBLIC_API_NESTED`:
    -   `true` (default): Returns nested JSON (e.g., `{"messages": {"welcome": "Hello"}}`)
    -   `false`: Returns dot-notation JSON (e.g., `{"messages.welcome": "Hello"}`)

### Usage

1.  Navigate to `/glosa` (or your configured prefix).
2.  **Add Locales**: Create the languages you want to support (e.g., `en`, `fr`, `es`).
3.  **Add Keys**: Create translation keys. You can use dot notation for grouping (e.g., `auth.failed`).
4.  **Translate**: Enter values for each key and locale directly in the grid.
5.  **Import**: Import existing JSON translation files.

## License

The MIT License (MIT).
