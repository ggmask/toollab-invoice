# ToolLab Invoice

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg)](https://php.net/)
[![Database](https://img.shields.io/badge/mysql-8.4-00758F.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![GST Compliance](https://img.shields.io/badge/GST-India%20Rule%2046-4F46E5.svg)](GST-LOGIC.md)

**ToolLab Invoice** is a modern, responsive, Indian-market invoicing SaaS application engineered specifically for Indian businesses, agencies, service providers, and traders. Built with clean PHP 8.4+, modern vanilla JavaScript, Tailwind CSS design system tokens, and MySQL, with **zero Node.js runtime dependency**.

---

## 🌟 Key Features

### 🇮🇳 Indian GST & Legal Compliance (Rule 46)
- **Automatic Tax Split**: Automatically determines Intra-State (**CGST + SGST**) vs Inter-State (**IGST**) based on the supplier's state and customer Place of Supply (POS).
- **HSN/SAC Catalog**: Complete support for Goods (HSN) and Services (SAC) codes with GST slab rates (0%, 5%, 12%, 18%, 28%).
- **Rule 46 Tax Invoice Layout**: Includes supplier & customer GSTIN, consecutive FY sequence numbering, Reverse Charge indicator, place of supply with state codes, bank remittance, and UPI VPA.
- **B2B vs B2C Classification**: Instant classification of outward supplies for automated GSTR-1 preparation.

### 💰 Invoicing & Estimates
- **Document Sequences**: Concurrency-safe financial year sequences (e.g., `INV/24-25/0001`, `EST/24-25/0001`, `PAY/24-25/0001`) with row-level locks.
- **Accurate Financial Arithmetic**: Pro-rated invoice-level discounts, line item discounts, rounding, and Indian numbering formatting (`₹1,23,456.78`).
- **Number to Words**: Native Indian rupee converter (`Rupees One Lakh Twenty-Three Thousand Four Hundred Fifty-Six and Seventy-Eight Paise Only`).
- **One-Click Estimate Conversion**: Convert approved quotes to formal tax invoices instantly with complete line item retention.
- **Recurring Invoice Automation**: Monthly, quarterly, bi-annual, and annual recurring profiles with automatic due date calculation and CLI runner.

### 💳 Payments & Receivables
- **Multi-Mode Collections**: Record UPI, NEFT/RTGS, Net Banking, Credit/Debit Cards, Cheques, and Cash.
- **Partial & Multi-Invoice Allocation**: Allocate a single payment across multiple outstanding invoices sequentially or manually.
- **Advance & Unallocated Funds**: Automatically tracks unallocated credit for future invoices.
- **Safe Payment Reversals**: Full audit trail when reversing bounced cheques or mistaken receipts, automatically restoring invoice balance due.

### 📊 Financial Reporting & Exports
- **Sales Register**: Detailed register with date filters, status breakdown, and taxable turnover metrics.
- **GSTR-1 Ready Register**: Itemized line-by-line GST report formatted for GST portal return filing.
- **GST Tax Summary**: Tax liability grouped by GST rate slab for GSTR-3B filing.
- **Receivables Ageing Analysis**: Working capital breakdown by overdue buckets (Not Due, 1–30, 31–60, 61–90, 90+ days).
- **Customer Account Statement**: Chronological debit/credit running ledger with net closing balance.
- **Universal Multi-Format Export**: RFC-4180 CSV with UTF-8 BOM and native SpreadsheetML Excel XML (`.xls`) with formula injection neutralization.
- **Vector PDF & Print Engine**: Clean Dompdf integration for printable Tax Invoices, Quotations, and Payment Receipts.

### 🔒 Security & Multi-Tenant Isolation
- Account isolation on every query via `business_id` scoping.
- Concurrency-safe transactions via PDO with prepared statements.
- Strict CSRF token validation on all mutating `POST`/`PUT`/`DELETE` requests.
- Secure session cookie management (`HttpOnly`, `SameSite=Lax`).
- Comprehensive audit logging (`audit_logs`) tracking creation, modification, reversals, and exports.

---

## 🛠️ Technology Stack

| Layer | Technology | Details |
|---|---|---|
| **Backend** | PHP 8.4+ | Pure object-oriented PHP with strict typing, PSR-4 autoloading |
| **Database** | MySQL 8.4+ (InnoDB) | UTF8MB4, foreign keys, row-level sequence locking |
| **PDF Engine** | Dompdf 3.1 | Clean HTML-to-PDF rendering with local asset embedding |
| **Exports** | RFC-4180 CSV & SpreadsheetML | Native multi-format generators with zero external C-extension requirements |
| **Frontend** | HTML5, Tailwind Tokens, ES6+ JS | Zero runtime Node.js requirement; clean responsive UI |
| **PWA** | Web App Manifest & Service Worker | Offline cache fallback and mobile home screen installation |

---

## 🚀 Installation & Local Setup (Laragon)

### Prerequisites
- [Laragon](https://laragon.org/) with PHP 8.4+ and MySQL 8.4+
- Composer 2.x

### 1. Clone & Place Workspace
Ensure the codebase is located in your Laragon root directory:
```bash
c:\laragon\www\toollab-invoice
```

### 2. Install Composer Dependencies
```bash
cd c:\laragon\www\toollab-invoice
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment
Copy `.env.example` to `.env` (already pre-configured for Laragon defaults):
```env
APP_NAME="ToolLab Invoice"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toollab_invoice
DB_USERNAME=root
DB_PASSWORD=""

SESSION_LIFETIME=120
SESSION_SECURE=false
```

### 4. Database Setup & Seeding
Create the database and run migrations & seeders:
```bash
# Create database and import schema
mysql -u root -e "CREATE DATABASE IF NOT EXISTS toollab_invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root toollab_invoice < database/schema.sql

# Seed initial business, demo invoices, tax rates, customers, and catalog
php database/seed.php
```

### 5. Start Application
You can use Laragon's virtual host `http://toollab-invoice.test` (via Apache) or PHP's built-in server:
```bash
php -S localhost:8000 -t public
```
Navigate to: **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Default Credentials

The seeder populates an initial administrator user and business:

- **URL**: `http://localhost:8000/login`
- **Email**: `admin@toollab.in`
- **Password**: `password123`
- **Company**: ToolLab Technologies Private Limited
- **GSTIN**: `27AAACT1234A1Z5` (Maharashtra, State Code `27`)

---

## ⏰ Recurring Invoices Automation Setup

To automatically process recurring invoice profiles when their next issue date arrives, configure a cron job or Windows Task Scheduler to run the CLI runner:

### Windows (Task Scheduler or Laragon)
Run daily at 00:05 AM:
```powershell
php C:\laragon\www\toollab-invoice\bin\process-recurring-invoices.php
```

### Linux / Crontab
```cron
5 0 * * * /usr/bin/php /var/www/toollab-invoice/bin/process-recurring-invoices.php >> /var/log/recurring-invoices.log 2>&1
```

The runner:
1. Identifies all `active` recurring profiles where `next_issue_date <= CURRENT_DATE()`.
2. Generates the next sequential invoice number concurrency-safely.
3. Clones recurring items and calculates taxes based on customer location.
4. Records an occurrence in `recurring_occurrences` and updates `next_issue_date`.
5. Automatically marks profiles as `completed` when `end_date` or `max_cycles` is reached.

---

## 📁 Project Architecture & Directory Structure

```
toollab-invoice/
├── app/
│   ├── Controllers/         # Clean thin controllers handling HTTP requests
│   ├── Middleware/          # AuthMiddleware, GuestMiddleware, CsrfMiddleware
│   ├── Services/            # Business logic: TaxCalculation, DocumentNumber, Invoicing, Export, Pdf
│   ├── Support/             # Database, Router, Request, Response, Session, Csrf, View, Currency
│   └── Views/               # Semantic PHP templates with layouts & components
├── bin/                     # CLI console scripts (process-recurring-invoices.php)
├── config/                  # Configuration files (app.php, database.php, constants.php)
├── database/                # schema.sql and seed.php
├── public/                  # Document root
│   ├── assets/              # Handcrafted CSS & JS (app.css, app.js)
│   ├── favicon.png          # ToolLab brand favicon
│   ├── logo.png             # ToolLab brand logo
│   ├── index.php            # Front controller entrypoint
│   └── sw.js, manifest.json # Progressive Web App support
├── routes/                  # web.php route definitions
└── docs/                    # Architectural & compliance documentation
```

---

## 📖 In-Depth Documentation

For architectural specifics, financial logic, and deployment guides, refer to:
- [Architecture & Design Model](ARCHITECTURE.md)
- [Database Schema & Sequence Allocation](DATABASE.md)
- [Indian GST Calculation Logic](GST-LOGIC.md)
- [Invoice State Machine Lifecycle](INVOICE-LIFECYCLE.md)
- [Payment Allocation & Reversals](PAYMENTS.md)
- [Financial Reports & Compliance](REPORTS.md)
- [Security & Isolation Model](SECURITY.md)
- [Production Deployment Guide](DEPLOYMENT.md)

---

## 📄 License
This software is developed for ToolLab. All rights reserved.
