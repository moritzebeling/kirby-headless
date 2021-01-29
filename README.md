# Kirby Headless

This plugin adds a public Json API endpoint to your Kirby website.

## Route
Through the `/json/...` route you can retrieve a predefined dataset for every page and file:
- `/json/site` site data
- `/json/{page id}` page data

## Response
The route triggers a custom `json` method on either site, pages or files. You can extend it via page models.
