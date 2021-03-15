# Kirby Headless

This plugin adds a public Json API endpoint to your Kirby website.

## Route

Through the `/json/...` route you can retrieve a predefined dataset for every page and file:
- `/json/site` site data
- `/json/{page id}` page data

## Response

The plugin adds the following methods:

- `$site->json(): array`
- `$page->json( bool $full = false ): array`
- `$pages->json( bool $full = false ): array`
- `$file->json( string $thumbSize = 'l' ): array`
- `$files->json( string $thumbSize = 'l' ): array`

which are triggered within the route. Take a look at the code to see what fields are included by default.

## Installation

It’s probably recommended to not use this plugin as a submodule, because you might want to edit it to match your needs.

## CORS Settings

To reach your API endpoint from a remote server, you will need to allow Cross Origin Resource Sharing. Besides HTTPS, these rules might help you to set it up:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET, OPTIONS, HEAD, CONNECT, POST');
header('Access-Control-Expose-Headers: *');
header('Access-Control-Max-Age: 5');
header('Timing-Allow-Origin: *');
header('Content-Type: application/json');
```

## Warning

Please note that the provided enpoint is public and doesn’t require auth. That means that anyone can access it. So please use it only for non-sensible data.
