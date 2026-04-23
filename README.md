# Survice

A Laravel 11 API for **lead generation** powered by the [Lusha](https://www.lusha.com/) B2B intelligence platform.

---

## Features

| Feature | Description |
|---------|-------------|
| **Prospecting** | Discover new leads using Lusha's prospecting engine (filter by job title, company, country, industry, seniority, etc.) |
| **Person Enrichment** | Look up email addresses and phone numbers for a known contact |
| **Company Enrichment** | Retrieve firmographic data by domain or company name |
| **Lead Import** | Persist Lusha contacts into the local `leads` table |
| **Lead Management** | List, filter, update status, and soft-delete stored leads |

---

## Requirements

- PHP 8.1+
- Composer
- A valid [Lusha API key](https://dashboard.lusha.com/settings/api)

---

## Installation

```bash
git clone https://github.com/ahmedmostafafeky1/Survice.git
cd Survice

composer install

cp .env.example .env
php artisan key:generate

# Add your Lusha API key to .env
# LUSHA_API_KEY=your_key_here

php artisan migrate
php artisan serve
```

---

## API Endpoints

All endpoints are prefixed with `/api/leads`.

### Prospecting

```
GET /api/leads/prospect
```

Query parameters (all optional):

| Parameter | Type | Example |
|-----------|------|---------|
| `job_title` | string | `"Software Engineer"` |
| `company_name` | string | `"Acme Corp"` |
| `country` | string (ISO 2) | `"US"` |
| `industry` | string | `"Technology"` |
| `company_size` | string | `"11-50"` |
| `department` | string | `"Engineering"` |
| `seniority_level` | string | `"Manager"` |
| `page` | int | `1` |
| `page_size` | int | `25` |

### Person Enrichment

```
POST /api/leads/enrich/person
Content-Type: application/json

{
  "first_name": "Jane",
  "last_name":  "Doe",
  "company":    "acme.com"
}
```

Saves the enriched contact as a Lead and returns both the raw Lusha data and the saved record.

### Company Enrichment

```
POST /api/leads/enrich/company
Content-Type: application/json

{
  "domain": "acme.com"
}
```

### Bulk Import

```
POST /api/leads/import
Content-Type: application/json

{
  "contacts": [
    { "id": "lusha-id-1", "firstName": "Jane", "lastName": "Doe", ... },
    { "firstName": "John", "lastName": "Smith", ... }
  ]
}
```

### Lead CRUD

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/leads` | List all leads (supports `?status=` and `?search=`) |
| `GET` | `/api/leads/{id}` | Get a single lead |
| `PATCH` | `/api/leads/{id}` | Update lead status (`pending`/`qualified`/`disqualified`/`converted`) |
| `DELETE` | `/api/leads/{id}` | Soft-delete a lead |

---

## Configuration

All Lusha settings live in `config/lusha.php` and are driven by environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `LUSHA_API_KEY` | _(required)_ | Your Lusha API key |
| `LUSHA_BASE_URL` | `https://api.lusha.com` | API base URL |
| `LUSHA_TIMEOUT` | `30` | HTTP timeout in seconds |
| `LUSHA_DEFAULT_LIMIT` | `25` | Default page size for prospecting |

---

## Running Tests

```bash
php artisan test
# or
./vendor/bin/phpunit
```