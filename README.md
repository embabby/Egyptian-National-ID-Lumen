# Egyptian National ID Validator API

A Lumen (Laravel) API that validates Egyptian national ID numbers and extracts encoded data (birth date, gender, governorate). It includes rate limiting, API key authentication, and optional call tracking for billing.

## Requirements

- PHP 8.1+
- Composer
- MySQL

## Installation

```bash
composer install
cp .env.example .env
```

### Database (MySQL)

Create a MySQL database, then set in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

### Create an API key

Generate a new API key (store the output securely; the raw key is only shown once):

```bash
php artisan api-key:generate --name="My Service"
```

Or seed a development key:

```bash
php artisan db:seed
# Note the "Development API key" printed; add it to .env or use in requests.
```

## Running the application

```bash
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`.

## API

### Health check (no authentication)

- **GET** `/api/v1/health`  
  Returns `{"status":"ok","service":"egyptian-id-api"}`.

### Validate & extract national ID

- **POST** or **GET** `/api/v1/national-id/validate`

**Authentication:** Required. Send one of:

- Header: `X-API-Key: <your-api-key>`
- Header: `Authorization: Bearer <your-api-key>`

**Rate limit:** 60 requests per minute per API key (configurable in route middleware).

**Request**

- **POST** body (JSON): `{"national_id": "29001011234567"}`
- **GET** query: `?national_id=29001011234567`

The national ID must be exactly 14 digits (spaces are stripped).

**Response (200)**

- Valid ID:

```json
{
  "valid": true,
  "national_id": "29506202110012",
  "data": {
    "birth_date": "1995-06-20",
    "birth_year": 1995,
    "birth_month": 6,
    "birth_day": 20,
    "gender": "male",
    "governorate": {
      "code": "21",
      "name": "Giza"
    }
  }
}
```

- Invalid ID:

```json
{
  "valid": false,
  "national_id": "00000000000000",
  "data": null
}
```

**Error responses**

- **401** – Missing or invalid API key.
- **422** – Validation error (e.g. missing or invalid `national_id`).
- **429** – Too many requests (rate limit).

## Egyptian national ID format

The 14-digit ID is structured as:

- **Digit 1:** Century (2 = 1900–1999, 3 = 2000–2099)
- **Digits 2–7:** Birth date (YYMMDD)
- **Digits 8–9:** Governorate code (official Civil Registry codes)
- **Digit 10:** Gender (odd = male, even = female)
- **Digits 11–13:** Serial number
- **Digit 14:** Luhn check digit

Validation checks length, numeric format, Luhn check digit, and plausibility of date and governorate code.

## Design and implementation notes

1. **Framework:** Lumen was chosen for a minimal, fast API with Laravel’s routing, validation, and database stack.
2. **Validation & extraction:** Logic lives in `App\Services\EgyptianNationalIdService`: Luhn check digit, century/date/governorate rules, and governorate list from official sources (e.g. Egyptian Civil Registry / Wikipedia).
3. **Authentication:** API keys are stored as SHA-256 hashes in `api_keys`; the raw key is never stored. Keys can be sent via `X-API-Key` or `Authorization: Bearer`.
4. **Rate limiting:** Per–API-key limit (e.g. 60/minute) is enforced in middleware using the cache; limit and window are set on the route (`rate.limit:60,1`).
5. **Call tracking (bonus):** Each request to the national-id endpoint is recorded in `api_calls` (api_key_id, endpoint, method, national_id requested, response status, IP, user agent). This supports usage tracking and future billing logic; no charge calculation is implemented.
6. **Database:** MySQL. Migrations create `api_keys` and `api_calls` (and migrations table).

## Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

- **Unit tests:** `EgyptianNationalIdService` (validation, extraction, normalization).
- **Feature tests:** Health, auth required, validation errors, valid ID response, Bearer token.

Tests use MySQL. Create a test database (e.g. `egyptian_id_test`) and set `DB_*` in `.env.testing` or phpunit.xml; migrations run automatically for feature tests.

## Project structure (relevant parts)

```
app/
  Console/Commands/GenerateApiKeyCommand.php   # api-key:generate
  Http/
    Controllers/NationalIdController.php      # validate endpoint
    Middleware/
      ApiKeyAuthMiddleware.php
      RateLimitMiddleware.php
      TrackApiCallMiddleware.php
  Models/ApiKey.php, ApiCall.php
  Services/EgyptianNationalIdService.php
config/database.php, cache.php
database/migrations/   # api_keys, api_calls
routes/web.php
```
