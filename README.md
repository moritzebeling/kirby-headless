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
- `$file->json( bool $includeParent = false ): array`
- `$files->json( bool $includeParent = false ): array`

which are triggered within the route. Take a look at the code to see what fields are included by default.

## Installation

It’s probably recommended to not use this plugin as a submodule, because you might want to edit it to match your needs.

## Warning

Please note that the provided enpoint is public and doesn’t require auth. That means that anyone can access it. So please use it only for non-sensible data.
