# API Documentation - e-ticketing-api

## Base URL

- Local: `http://localhost:8000/api/v1`
- Semua endpoint API berada di prefix `/api/v1`.

## Authentication

- Auth menggunakan **Laravel Sanctum** (Bearer Token).
- Login melalui `POST /login`, lalu kirim header:

`Authorization: Bearer <token>`

## Standard Response Format

Sebagian besar endpoint menggunakan format:

```json
{
  "success": true,
  "message": "Success message",
  "data": {}
}
```

Untuk list paginated:

```json
{
  "success": true,
  "message": "Data Retrieved Successfully",
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 0
  }
}
```

## Roles

Role user yang digunakan:

- `admin`
- `team_lead`
- `it_staff`
- `reporter`

### Akses umum (auth + throttle)

Bisa diakses role: `admin`, `it_staff`, `reporter`, `team_lead`:

- `GET /user`
- Read endpoints untuk ticket, error report, feature request
- Comment endpoints (index/store/destroy)

### Akses khusus it_staff

Hanya role `it_staff`:

- Create/update/delete ticket
- Create/update/delete error report
- Create/update/delete feature request
- Convert ticket
- Approve/reject feature request

---

## Auth Endpoints

### POST `/register`

Membuat user baru.

Request body:

- `name` (required, string, max 255)
- `email` (required, email, unique)
- `password` (required, min 8, confirmed)
- `password_confirmation` (required)
- `role` (required, enum: `admin`, `team_lead`, `it_staff`, `reporter`)
- `team` (nullable, enum: `programmer`, `network`, `hardware`)
- `avatar` (nullable, url)
- `is_active` (boolean)
- `pref_email_notifications` (nullable, boolean)
- `pref_sla_alerts` (nullable, boolean)
- `pref_downtime_alerts` (nullable, boolean)
- `pref_digest_frequency` (nullable, enum: `immediate`, `hourly`, `daily`, `weekly`)
- `pref_quiet_hours` (nullable, format `HH:MM-HH:MM`)

### POST `/login`

Login user dan menghasilkan token.

Request body:

- `email` (required, email)
- `password` (required, string)

Response utama:

- `user`
- `token`

### POST `/logout`

Mencabut current access token user (auth required).

### POST `/forgot-password`

Request body:

- `email` (required, email)

### POST `/reset-password`

Request body:

- `token` (required)
- `email` (required, email)
- `password` (required, confirmed)
- `password_confirmation` (required)

### GET `/verify-email/{id}/{hash}`

Verifikasi email (auth + signed URL + throttle). Endpoint ini melakukan redirect ke frontend.

### POST `/email/verification-notification`

Kirim ulang email verifikasi (auth required).

---

## User Endpoint

### GET `/user`

Mengembalikan data user login saat ini.

---

## Ticket Endpoints

### GET `/tickets`

List ticket (paginated, 10 per page).

### GET `/tickets/{ticket}`

Detail ticket.

### POST `/tickets` (it_staff)

Membuat ticket baru.

Request body:

- `title` (required, string, max 255)
- `description` (required, string)
- `category` (required, enum: `software_bug`, `feature_request`, `network_issue`, `hardware_problem`, `system_error`, `performance_issue`)
- `priority` (required, enum: `low`, `medium`, `high`, `critical`)
- `assigned_to_id` (nullable, integer, exists users)
- `assigned_team` (nullable, enum: `programmer`, `network`, `hardware`)
- `due_date` (nullable, date)
- `response_time` (nullable, decimal)
- `resolution_time` (nullable, decimal)
- `estimated_effort` (nullable, decimal)
- `parent_ticket_id` (nullable, exists tickets)

### PUT `/tickets/{ticket}` (it_staff)

Update ticket (field bersifat `sometimes/nullable` tergantung atribut).

Field penting:

- `title`, `description`, `category`, `priority`, `status`
- `reporter_id`, `assigned_to_id`, `assigned_team`
- `date_reported`, `due_date`, `resolved_date`, `closed_date`
- `sla_breached`, `response_time`, `resolution_time`
- `estimated_effort`, `actual_effort`
- `parent_ticket_id`
- `converted_to_type` (`error_report` | `feature_request`)
- `converted_to_id`, `converted_at`, `conversion_reason`

### DELETE `/tickets/{ticket}` (it_staff)

Hapus ticket.

### POST `/tickets/{ticket}/convert/error-report` (it_staff)

Convert ticket menjadi error report.

Request body utama:

- `title` (required)
- `description` (required)
- `category` (required, enum error report)
- `priority` (required)
- `assigned_to_id` (nullable)
- `assigned_team` (nullable)
- `start_date` (nullable)
- `due_date` (nullable)
- `estimated_effort` (nullable)
- `conversion_reason` (required)

Catatan: beberapa field diisi otomatis oleh backend saat konversi (mis. `reporter_id`, `date_reported`, `status`, `is_direct_input`).

### POST `/tickets/{ticket}/convert/feature-request` (it_staff)

Convert ticket menjadi feature request.

Request body utama:

- `title` (required)
- `description` (required)
- `request_type` (required, `feature_request` | `bug_fix`)
- `priority` (required)
- `assigned_to_id` (nullable)
- `assigned_team` (nullable)
- `assignment_date` (nullable)
- `start_date` (nullable)
- `due_date` (nullable)
- `review_date` (nullable)
- `estimated_effort` (nullable)
- `roi_impact` (nullable)
- `quality_impact` (nullable)
- `conversion_reason` (required)

---

## Error Report Endpoints

### GET `/error-reports`

List error report (paginated).

### GET `/error-reports/{error}`

Detail error report.

### POST `/error-reports` (it_staff)

Membuat error report baru.

Request body:

- `title` (required)
- `description` (required)
- `category` (required, `hardware` | `network` | `software`)
- `priority` (required, `low` | `medium` | `high` | `critical`)
- `assigned_to_id` (nullable)
- `assigned_team` (nullable, `programmer` | `network` | `hardware`)
- `start_date` (nullable)
- `due_date` (nullable)
- `estimated_effort` (nullable)
- `source_ticket_id` (nullable)

### PUT `/error-reports/{error}` (it_staff)

Update error report (partial update).

Field umum:

- `title`, `description`, `category`, `priority`, `status`
- `reporter_id`, `assigned_to_id`, `assigned_team`
- `date_reported`, `start_date`, `due_date`, `completion_date`
- `estimated_effort`, `actual_effort`
- `sla_time_elapsed`, `sla_time_remaining`, `sla_breached`
- `source_ticket_id`, `is_direct_input`

### DELETE `/error-reports/{error}` (it_staff)

Hapus error report.

---

## Feature Request Endpoints

### GET `/feature-requests`

List feature request (paginated).

### GET `/feature-requests/{feature}`

Detail feature request.

### POST `/feature-requests` (it_staff)

Membuat feature request baru.

Request body:

- `title` (required)
- `description` (required)
- `request_type` (required, `feature_request` | `bug_fix`)
- `priority` (required)
- `assigned_to_id` (nullable)
- `assigned_team` (nullable)
- `assignment_date` (nullable)
- `start_date` (nullable)
- `due_date` (nullable)
- `review_date` (nullable)
- `estimated_effort` (nullable)
- `roi_impact` (nullable)
- `quality_impact` (nullable)
- `source_ticket_id` (nullable)

### PUT `/feature-requests/{feature}` (it_staff)

Update feature request (partial update).

Field umum:

- `title`, `description`, `request_type`, `priority`, `status`, `progress`
- `reporter_id`, `assigned_to_id`, `assigned_team`
- `date_submitted`, `approval_date`, `assignment_date`, `start_date`, `due_date`, `completion_date`, `review_date`
- `estimated_effort`, `actual_effort`
- `sla_time_elapsed`, `sla_time_remaining`, `sla_breached`
- `approved_by`, `rejection_reason`
- `roi_impact`, `quality_impact`, `post_implementation_notes`
- `source_ticket_id`, `is_direct_input`

### DELETE `/feature-requests/{feature}` (it_staff)

Hapus feature request.

### POST `/feature-requests/{feature}/approve` (it_staff)

Approve atau reject feature request.

Request body:

- `status` (required, `approved` | `rejected`)
- `rejection_reason` (required jika `status = rejected`)

Catatan: `approved_by` dan `approval_date` diisi otomatis backend.

---

## Comment Endpoints

Comment terhubung ke resource parent (ticket/error report/feature request).

### Ticket comments

- `GET /tickets/{ticket}/comments`
- `POST /tickets/{ticket}/comments`
- `DELETE /tickets/{ticket}/comments/{comment}`

### Error report comments

- `GET /errors/{error}/comments`
- `POST /errors/{error}/comments`
- `DELETE /errors/{error}/comments/{comment}`

### Feature request comments

- `GET /features/{feature}/comments`
- `POST /features/{feature}/comments`
- `DELETE /features/{feature}/comments/{comment}`

Request body saat create comment:

- `content` (required, string)

Catatan:

- `user_id` comment diisi dari user login.
- Untuk user `it_staff`, komentar otomatis ditandai internal oleh backend.

---

## Quick Example

### Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### Get tickets

```bash
curl -X GET http://localhost:8000/api/v1/tickets \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json"
```
