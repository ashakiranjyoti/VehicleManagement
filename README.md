# Soft Vehicle Management

A lightweight PHP/MySQL web application to manage vehicles, services, tools, engineers and related reports. Built for small organizations to track vehicle maintenance, tool assignments and generate PDF/Excel reports.

--

**Tech Stack:** PHP (vanilla), MySQL / MariaDB, Bootstrap 5, TCPDF (for PDF exports), vanilla JavaScript.

**Minimum Requirements:**
- PHP 7.4+ (PHP 8 recommended)
- MySQL / MariaDB
- Apache (XAMPP recommended for development on Windows)

**Database:** `vehicle_management` (see `db/vehicle_management (3).sql`)

## Quick Overview

- User authentication and roles (`admin` and `user`).
- CRUD for vehicles, services, tools, engineers, and users.
- Tool assignment tracking and history for engineers.
- Service records with file uploads for bills/receipts.
- Export to Excel, CSV and PDF (uses `services/TCPDF-main`).
- Basic reporting pages (monthly, financial, cost analysis).

## Getting Started (Run locally)

1. Install XAMPP (Apache + PHP + MySQL) on Windows.
2. Copy the project into XAMPP's `htdocs` folder as `VehicleManagement`.
3. Start Apache and MySQL from the XAMPP control panel.
4. Import the database dump:

   - Using phpMyAdmin: create a database named `vehicle_management` and import `db/vehicle_management (3).sql`.
   - Using MySQL CLI (example):

     ```bash
     mysql -u root -p < "db/vehicle_management (3).sql"
     ```

5. Edit database connection if needed: `config/database.php` (host, username, password, database).
6. (Optional) Update `BASE_URL` in `includes/auth.php` if the app is hosted in a different subdirectory.
7. Open the app in your browser: `http://localhost/soft_vehicle_management` and log in.

## Important Files & Folders

- `index.php` — Login page and authentication entry point.
- `dashboard.php` — Main landing after login (contains navigation to modules).
- `config/database.php` — MySQL connection (used across the app).
- `includes/auth.php` — Session handling, login helper and role helpers (`isAdmin()`, `isLoggedIn()`).
- `includes/header.php`, `includes/footer.php`, `includes/navigation.php` — Common layout and menu.
- `services/` — Service-related pages (view, add, edit, export). Example: `services/view_services.php` shows filtering, table listing and export hooks.
- `services/TCPDF-main/` — TCPDF library and examples used for PDF export.
- `vehicles/` — Vehicle CRUD pages.
- `uploads/` — Uploaded bills/receipts (should be gitignored in public repos).
- `db/vehicle_management (3).sql` — SQL dump to create schema and initial tables.

## Database Schema (summary)

Key tables (see the SQL dump for full column details):

- `users` — application users with `username`, `password`, `role` (admin/user).
- `vehicles` — vehicle master (type, make_model, reg_number, chassis_number, owner_name).
- `services` — service records tied to vehicles (date, time, service_type, cost, bill_file, description).
- `tools`, `engineers`, `engineer_tools`, `tool_history` — tool inventory, engineers, assignments and history.

## How the Service Module Works (example)

- `services/view_services.php` loads and displays service records with filters (search, type, date range).
- Filters are applied via GET parameters and used to build a SQL `WHERE` clause.
- Each service row can show uploaded bill links and action buttons (`edit`, `delete` for admins).
- Exports call `export_excel.php`, `export_pdf.php` (PDFs use TCPDF) with current filter params.

## Security Notes & Recommended Improvements

- Passwords in the provided SQL/code appear to be stored and compared as plain text. Replace with `password_hash()` and `password_verify()`.
- Use prepared statements consistently to avoid SQL injection (some files already use `$conn->real_escape_string` but prepared statements are better).
- Protect uploads directory (`uploads/`) with an `.htaccess` rule to prevent serving arbitrary files, or store files outside webroot and serve via controlled script.
- Remove database credentials before publishing on GitHub or use environment variables/config templates.

## How to Add This Project to GitHub (recommended checklist)

1. Create a new repository on GitHub.
2. Add a `.gitignore` and exclude `uploads/`, `db/*.sql` (optional) and vendor/lib if any.
   Example `.gitignore` lines:

   ```gitignore
   /uploads/
   /vendor/
   config/database.php
   *.sql
   ```

3. Replace or remove production credentials from `config/database.php`. Instead, commit a `config/database.example.php` with placeholders.
4. Commit and push the code:

   ```bash
   git init
   git add .
   git commit -m "Initial commit: Soft Vehicle Management"
   git branch -M main
   git remote add origin <your-repo-url>
   git push -u origin main
   ```

## Notes for Interview Explaining Scope

- Describe the problem domain: tracking vehicles, services, tools and engineers to ensure maintenance records and tool accountability.
- Explain core flows: user auth → dashboard → choose module (vehicles/services/tools) → perform CRUD → generate reports/exports.
- Point out real-world features: file uploads for bills, service cost tracking, export to Excel/PDF, tool assignment history.
- Mention improvements you would make: secure passwords, role-based access control improvements, API endpoints, React/SPA frontend, unit tests, CI/CD.

## Next Steps I Can Help With

- Create a `config/database.example.php` and update code to load environment-based config.
- Add `.gitignore` and prepare the project for a safe public GitHub push.
- Produce a short one-page interview cheat-sheet summarizing key features and code locations.

---

If you want, I can now:
- create a `config/database.example.php` and `.gitignore`,
- or generate a concise interview cheat-sheet extracted from this README.
