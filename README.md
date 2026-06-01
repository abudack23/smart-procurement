# Smart Procurement and Bidding System

## Overview
A web-based procurement platform with two user roles:
- **Admin**: create procurement requests, evaluate bids, award suppliers, and review reports.
- **Supplier**: register, browse open procurement opportunities, submit bids, track results, and manage profile.

The system includes predictive bid evaluation and simple decision support based on historical data.

## Setup
1. Place this folder in your XAMPP `htdocs` directory.
2. Start Apache and MySQL.
3. Open `http://localhost/phpmyadmin`.
4. Import `db_init.sql` or run the SQL script.
5. Update database credentials in `includes/config.php` if needed.

## Deployment
This repository now uses a single PHP/Tailwind-based deployment model under `http://localhost/Smart-Procurement/`.
The React/Vite UI scaffold previously present in `frontend/` is archived and not part of the live XAMPP deployment.

## Usage
- Open `http://localhost/Smart-Procurement/index.php`
- Suppliers register at `http://localhost/Smart-Procurement/register.php`
- Supplier login: `http://localhost/Smart-Procurement/supplier_login.php`
- Admin login: `http://localhost/Smart-Procurement/admin_login.php` (no public admin registration)
- Suppliers are automatically assigned `role='supplier'` through public registration.
- Admin accounts must be created manually in the database or by an authorized admin user. Public registration cannot create admin accounts.
- Admins can create procurements, view bidders, evaluate and award bids.
- System sends email notifications for new opportunities, bid submissions, and award results.
- Suppliers can search/filter opportunities, submit bids, and view smart pricing guidance.
- Interactive graphs and charts support admin and supplier dashboards.

## Important Files
- `index.php` - PHP frontend entrypoint and public landing page
- `login.php`, `register.php`, `dashboard.php` - core PHP pages with role-based admin/supplier access
- `includes/layout.php` - Tailwind UI layout and navigation
- `includes/functions.php` - shared database helpers, authentication, notifications, and form processing
- `db_init.sql` - database schema initialization

## Notes
- Uploaded files are stored in the `uploads/` directory.
- Admin retains control over final supplier selection.
- The system uses basic heuristic scoring for price, delivery, reliability, and risk.
