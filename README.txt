# EHR System — Patient Management Application

A full-stack Electronic Health Record (EHR) system built with PHP and MySQL. Supports patient registration, clinical documentation, file uploads, and a complete audit trail of every change made to patient data.

Built solo as an academic project — designed, developed, tested, and deployed end to end.

## Features

- **Doctor authentication** — secure login/registration with hashed passwords
- **Patient management** — full CRUD (create, view, edit, delete) with structured profiles (demographics, contact info, blood type, allergies, emergency contacts)
- **Clinical documentation** — SOAP notes, diagnosis records, appointment/meeting logs, free-text clinical notes
- **Document management** — upload, store, and delete patient documents (lab reports, scans, etc.)
- **Anatomical diagrams** — interactive drawing tool for marking findings on body system diagrams
- **Audit logging** — every create, update, and delete on patient records is logged with a full before/after snapshot, timestamp, and the doctor responsible — viewable in a dedicated audit log page
- **Profile photos** — patient photo upload and management
- **Access control** — doctors can only view/edit/delete their own patients

## Tech Stack

- **Backend:** PHP (mysqli + PDO)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5, vanilla JS
- **Containerization:** Docker
- **CI/CD:** GitHub Actions
- **Testing:** pytest

## Database Schema

8 tables: `doctors`, `patients`, `patient_documents`, `patient_forms`, `patient_meetings`, `patient_diagnosis`, `patient_notes`, `audit_logs`.

See [`full_schema.sql`](./full_schema.sql) for the complete schema.

## Setup (Local — XAMPP)

**Requirements:** XAMPP (PHP 8+, MySQL/MariaDB), a web browser

1. Clone this repo into your XAMPP `htdocs` folder:
   ```
   git clone https://github.com/zainabmalik1532004-ux/ehr_system.git
   ```
   (Windows: `C:\xampp\htdocs\ehr_system` · macOS: `/Applications/XAMPP/htdocs/ehr_system`)

2. Start **Apache** and **MySQL** in the XAMPP Control Panel.

3. Create the database:
   - Open `http://localhost/phpmyadmin`
   - Create a new database named `ehr_system`
   - Open the **SQL** tab and run [`full_schema.sql`](./full_schema.sql)

4. Verify the connection settings in `db_connect.php` and `db_config.php` match your local setup (default: host `localhost`, user `root`, no password).

5. Visit `http://localhost/ehr_system/register.php` to create a doctor account, then log in.

## Setup (Docker)

```
docker build -t ehr-system .
docker run -p 8080:80 ehr-system
```
Note: you'll still need a MySQL instance reachable by the container, with the schema from `full_schema.sql` applied.

## Audit Logging

Every change to patient data is recorded in `audit_logs`:

| Field | Description |
|---|---|
| `action` | `CREATE`, `UPDATE`, or `DELETE` |
| `table_name` / `record_id` | which record was affected |
| `old_values` / `new_values` | JSON snapshot before/after the change |
| `doctor_id` | who made the change |
| `created_at` | when it happened |

View the trail at `audit_logs.php` after logging in.

## Project Context

Developed as part of the Health Informatics program at Deggendorf Institute of Technology, for the course *Information Systems of Healthcare*.

**Author:** Zainab Malik
