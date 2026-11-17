# API deprecation

A deprecated endpoint keeps working until its sunset date, and every response tells callers so through standard headers. The response body is unchanged, so existing clients don't break.

## Marking a route deprecated

Add the `sunset` middleware to the route's entry in `config/routes*.php`:

```php
'middleware' => ['sunset:<deprecated on>,<sunset on>[,<successor path>]'],
```

Dates are `YYYY-MM-DD` in UTC. The successor path is optional: give it when a newer endpoint replaces this one.

```php
// Replaced by a newer version
'middleware' => ['sunset:2026-11-17,2027-05-17,/api/v2/search/similar/datasets'],

// No replacement
'middleware' => ['sunset:2025-07-23,2026-01-22'],
```

Responses then carry:

| Header | Example | Meaning |
|---|---|---|
| `Deprecation` ([RFC 9745](https://www.rfc-editor.org/rfc/rfc9745)) | `@1794873600` | Unix time of the start of the deprecated-on day |
| `Sunset` ([RFC 8594](https://www.rfc-editor.org/rfc/rfc8594)) | `Mon, 17 May 2027 23:59:59 GMT` | End of the sunset-on day, when the route stops working |
| `Link` (only with a successor) | `</api/v2/search/similar/datasets>; rel="successor-version"` | The route to move to |

`SunsetHeaderTest` checks every route using `sunset` declares valid dates, with the deprecation date before the sunset date, so a typo fails CI rather than a live request.

Also mark the old path `deprecated=true` in its OpenAPI annotation, so it shows as deprecated in the API docs.

## Aliasing to the new version

Where the new version has the same request and response contract, point the old route at the new controller instead of keeping two implementations. `/api/v1/search/similar/datasets` does this.
