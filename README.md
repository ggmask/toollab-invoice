# ToolLab Invoice

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg)](https://php.net/)
[![Database](https://img.shields.io/badge/mysql-8.4-00758F.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![GST Compliance](https://img.shields.io/badge/GST-India%20Rule%2046-4F46E5.svg)](#-indian-gst--legal-compliance-rule-46)
[![Tests](https://img.shields.io/badge/tests-18%20passed%20%2F%20100%25-brightgreen.svg)](#-automated-testing)

**ToolLab Invoice** is a modern, high-performance, Indian GST-compliant invoicing, billing, and accounting web application engineered specifically for Indian businesses, agencies, freelancers, and enterprises.

Built with clean object-oriented PHP 8.4+, modern vanilla JavaScript (ES6+), Tailwind CSS tokens, MySQL (InnoDB), and Dompdf with **zero Node.js runtime dependency**.

---

## 📑 Table of Contents

- [Key Features](#-key-features)
  - [Indian GST & Legal Compliance](#-indian-gst--legal-compliance-rule-46)
  - [Invoicing, Estimates & Credit Notes](#-invoicing-estimates--credit-notes)
  - [Payments & Customer Statement Ledger](#-payments--customer-statement-ledger)
  - [Vector PDF Generation & Printing](#-vector-pdf-generation--printing)
  - [Financial Reporting & GSTR-1 Register](#-financial-reporting--gstr-1-register)
  - [Security & Multi-Tenant Isolation](#-security--multi-tenant-isolation)
- [Architecture & Design](#-architecture--design)
- [Dynamic URL & Domain Detection](#-dynamic-url--domain-detection)
- [System Requirements](#-system-requirements)
- [Local Installation (Laragon / Apache)](#-local-installation-laragon--apache)
- [Default Login Credentials](#-default-login-credentials)
- [Production Deployment](#-production-deployment)
- [Recurring Invoices Automation (Cron)](#-recurring-invoices-automation-cron)
- [Automated Testing](#-automated-testing)
- [Directory Structure](#-directory-structure)
- [License](#-license)

---

## 🌟 Key Features

### 🇮🇳 Indian GST & Legal Compliance (Rule 46)
- **Automatic Tax Split Engine**: Automatically determines Intra-State (**CGST + SGST**) vs Inter-State (**IGST**) based on the supplier's registered state and customer Place of Supply (POS) with two-digit state codes.
- **HSN & SAC Code Directory**: Built-in support for Goods (HSN) and Services (SAC) codes across all standard GST slabs (`0%`, `5%`, `12%`, `18%`, `28%`).
- **Section 15 Freight / Delivery Taxation**: Shipping and handling charges are taxed according to Indian GST principles (pro-rata distribution or highest applied GST slab).
- **Section 9 Reverse Charge Mechanism (RCM)**: Full flagging and reporting for reverse charge transactions.
- **Section 34 Credit Notes**: Issue formal credit notes linked to original tax invoices for sales returns, rate revisions, or post-sale discounts with automatic stock and ledger adjustments.
- **Rule 46 Compliant Invoices**: Includes legal business name, trade name, GSTIN, PAN, consecutive financial year sequential numbering, original invoice cross-references, bank remittance details, and dynamic UPI QR codes.

### 💰 Invoicing, Estimates & Credit Notes
- **Financial Year Document Sequences**: Concurrency-safe FY sequencing (e.g., `INV/24-25/0001`, `EST/24-25/0001`, `CN/24-25/0001`, `PAY/24-25/0001`) backed by MySQL InnoDB row-level locking.
- **Centralized Calculation Engine**: Shared financial arithmetic across UI editor, preview, PDF generation, and reports with exact integer-cent rounding, tax-inclusive reverse calculations, and round-off adjustments.
- **Number to Words**: Native Indian currency numbering converter formatted in Lakhs and Crores (`Rupees One Lakh Twenty-Three Thousand Four Hundred Fifty-Six and Seventy-Eight Paise Only`).
- **One-Click Estimate Conversion**: Seamlessly convert accepted quotations into finalized tax invoices while retaining line items, custom rates, and tax parameters.
- **Recurring Invoices**: Set up recurring billing profiles (monthly, quarterly, bi-annually, annually) with automated CLI background generation.

### 💳 Payments & Customer Statement Ledger
- **Multiple Payment Modes**: Record payments via UPI, NEFT/RTGS, IMPS, Net Banking, Credit/Debit Cards, Cheques, and Cash.
- **Multi-Invoice Allocation**: Allocate a single lump-sum customer payment across multiple outstanding invoices with automatic advance/credit tracking.
- **Customer Statement Ledger**: Running ledger report detailing debit (invoices), credit (payments, credit notes), and chronological net balance due.
- **Safe Payment Reversals**: Full audit trail when reversing bounced cheques or accidental entries, automatically restoring invoice balances.

### 📄 Vector PDF Generation & Printing
- **Native Indian Rupee (`₹`) Symbol**: Dompdf integration configured with the `DejaVu Sans` font family to prevent character encoding issues, question marks, or broken glyphs.
- **Repeating Headers & Footers**: Clean multi-page layouts with repeating table headers, page numbers (`Page X of Y`), and legal disclaimers.
- **HSN/SAC Summary Breakdown**: Itemized tax summary table detailing Taxable Value, Central Tax, State Tax, and Integrated Tax per HSN/SAC code.
- **Brand Customization**: Dynamic business logo embedding, bank account RTGS/NEFT details, UPI QR code, authorized signatory signature block, and customizable terms & conditions.

### 📊 Financial Reporting & GSTR-1 Register
- **GSTR-1 Sales Register**: Formatted outward supplies report ready for GST portal upload:
  - Table 4: B2B Invoices (registered buyers with GSTIN)
  - Table 5: B2C Large Invoices (inter-state $> ₹2.5 Lakhs to unregistered persons)
  - Table 7: B2C Small Invoices (intra-state and inter-state $\le ₹2.5$ Lakhs)
  - Table 8: Nil Rated, Exempted & Non-GST Outward Supplies
  - Table 12: HSN-wise Outward Summary
- **Receivables Ageing Report**: Categorizes outstanding balances into standard ageing brackets (`Current`, `1–30 Days`, `31–60 Days`, `61–90 Days`, `90+ Days`).
- **Sales & Tax Summaries**: Periodic sales turnover, tax liability breakdown by slab, and collection reports.
- **Export Formats**: Universal RFC-4180 CSV with UTF-8 BOM and native SpreadsheetML Excel XML (`.xls`) with formula injection protection.

### 🔒 Security & Multi-Tenant Isolation
- **Tenant Data Isolation**: Strict multi-tenant isolation where every query is scoped by `business_id` from the authenticated session.
- **SQL Injection Prevention**: 100% prepared statements via PHP Data Objects (PDO).
- **CSRF Protection**: Synchronizer token pattern validated on all mutating HTTP requests (`POST`, `PUT`, `DELETE`).
- **XSS Sanitization**: Context-aware output escaping via `e()` helper across all template views.
- **Brute Force Protection**: Rate-limited authentication controller to defend against credential stuffing.
- **Secure File Uploads**: Logo and signature uploads validate file extension, MIME type, image dimensions, and magic bytes.

---

## 🏗️ Architecture & Design

ToolLab Invoice is built on a clean, decoupled MVC architecture with strict separation of concerns:

```
toollab-invoice/
├── app/
│   ├── Controllers/         # Thin HTTP controllers handling input and response
│   ├── Middleware/          # AuthMiddleware, GuestMiddleware, CsrfMiddleware
│   ├── Services/            # Core business logic:
│   │                        #  - InvoiceCalculationService (centralized tax & discount math)
│   │                        #  - DocumentNumberService (FY sequential numbering with locks)
│   │                        #  - PdfService (Dompdf integration with DejaVu Sans)
│   │                        #  - ExportService (CSV & Excel SpreadsheetML generation)
│   ├── Support/             # Framework primitives (Database, Request, Router, View, Csrf)
│   └── Views/               # Semantic PHP templates with layouts, partials & components
├── bin/                     # CLI console utilities (process-recurring-invoices.php)
├── config/                  # Centralized configuration (app.php, database.php, constants.php)
├── database/                # schema.sql and seed.php
├── public/                  # Web server document root (index.php, assets, uploads)
│   └── assets/              # Handcrafted CSS & vanilla JS (zero Node.js dependency)
├── routes/                  # Route definitions (routes/web.php)
└── tests/                   # Automated PHPUnit test suite (Unit & Integration)
```

---

## 🌐 Dynamic URL & Domain Detection

ToolLab Invoice includes an intelligent routing and URL detection engine in `app/Support/Request.php` and `.htaccess`:

1. **Local Development (Subfolder Hosting)**:
   - Configured in `.env`: `APP_URL=https://localhost/toollab-invoice`
   - Access URL: `https://localhost/toollab-invoice/`
   - The root `.htaccess` automatically rewrites all requests to the `public/` directory internally.
   - The application strips `/public` and `/toollab-invoice` from the request path so routes match cleanly (`/login`, `/invoices`, etc.).
   - Assets and links generate full absolute URLs matching the base subfolder.

2. **Production Deployment (Root Domain / Subdomain)**:
   - Configured in `.env`: `APP_URL=https://invoicing.yourdomain.com`
   - Access URL: `https://invoicing.yourdomain.com/`
   - The application automatically detects that `APP_URL` is a root domain (no subfolder path).
   - No path stripping occurs, allowing standard virtual host root routing without modifying any application code.

3. **Automatic HTTPS Enforcement**:
   - The `.htaccess` file detects direct SSL (`HTTPS on`) as well as reverse proxies/load balancers (`HTTP:X-Forwarded-Proto https`) to prevent infinite redirect loops behind Cloudflare, AWS ALB, or Nginx reverse proxies.

---

## 💻 System Requirements

- **PHP**: `8.2` or `8.4+` (with `pdo_mysql`, `mbstring`, `gd`, `intl`, `xml`, `curl` extensions enabled)
- **Web Server**: Apache 2.4+ (with `mod_rewrite` enabled) or Nginx
- **Database**: MySQL 8.0+ or MariaDB 10.5+ (InnoDB storage engine)
- **Package Manager**: Composer 2.x
- **Node.js**: **None** (Zero Node.js dependency)

---

## 🚀 Local Installation (Laragon / Apache)

### 1. Place Project in Web Root
If you are using Laragon on Windows, clone or copy the project into:
```text
C:\laragon\www\toollab-invoice
```

### 2. Install Composer Dependencies
Open your terminal in the project directory and run:
```bash
composer install --optimize-autoloader
```

### 3. Environment Configuration
Verify that `.env` exists (or copy from `.env.example`):
```env
APP_NAME="ToolLab Invoice"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://localhost/toollab-invoice

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toollab_invoice
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Setup & Seeding
Create the database and load the schema and seed data:
```bash
# 1. Create database and import schema
mysql -u root -e "CREATE DATABASE IF NOT EXISTS toollab_invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root toollab_invoice < database/schema.sql

# 2. Seed initial demo company, customers, catalog, and sample invoices
php database/seed.php
```

### 5. Access the Application
Ensure Apache and MySQL are running in Laragon (with SSL enabled).
Open your browser and navigate to:
```text
https://localhost/toollab-invoice/
```
The application will automatically redirect to `https://localhost/toollab-invoice/login`.

---

## 🔑 Default Login Credentials

The database seeder provisions an administrator account with pre-configured GST business details:

| Field | Value |
|---|---|
| **Login URL** | `https://localhost/toollab-invoice/login` |
| **Email** | `admin@toollab.in` |
| **Password** | `password123` |
| **Business Name** | ToolLab Technologies Private Limited |
| **GSTIN** | `27AAACT1234A1Z5` (Maharashtra, State Code `27`) |
| **PAN** | `AAACT1234A` |

---

## 🌐 Production Deployment

### 1. Production `.env` Settings
On your production server, update `.env`:
```env
APP_NAME="ToolLab Invoice"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://invoicing.yourdomain.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_prod_db
DB_USERNAME=your_prod_user
DB_PASSWORD=your_secure_password
```

### 2. Apache Virtual Host (Recommended)
Point the `DocumentRoot` to the project's `public/` directory (or use the root directory with the bundled `.htaccess`):

```apache
<VirtualHost *:443>
    ServerName invoicing.yourdomain.com
    DocumentRoot /var/www/toollab-invoice/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/invoicing.yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/invoicing.yourdomain.com/privkey.pem

    <Directory /var/www/toollab-invoice/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/invoice_error.log
    CustomLog ${APACHE_LOG_DIR}/invoice_access.log combined
</VirtualHost>
```

### 3. File & Directory Permissions
Ensure the upload directory and cache directories are writable by the web server user (`www-data` on Ubuntu/Debian):
```bash
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

---

## ⏰ Recurring Invoices Automation (Cron)

To automatically generate recurring invoices when their scheduled issue date arrives, configure a daily cron job:

### Linux / Crontab
Run `crontab -e` and add:
```cron
5 0 * * * /usr/bin/php /var/www/toollab-invoice/bin/process-recurring-invoices.php >> /var/log/recurring-invoices.log 2>&1
```

### Windows Task Scheduler (Laragon)
Create a scheduled task to run daily at 00:05 AM:
```powershell
php C:\laragon\www\toollab-invoice\bin\process-recurring-invoices.php
```

The runner:
1. Finds all active profiles where `next_issue_date <= CURRENT_DATE()`.
2. Generates the next sequential invoice number with row-level locks.
3. Calculates taxes based on customer location and applies current pricing.
4. Records the occurrence in `recurring_occurrences` and updates `next_issue_date`.
5. Automatically marks profiles as `completed` when `end_date` or `max_cycles` is reached.

---

## 🧪 Automated Testing

ToolLab Invoice includes an automated test suite powered by PHPUnit testing unit calculations, tax splitting, credit notes, document numbering, and dynamic URL routing.

To run the test suite:
```bash
vendor/bin/phpunit
```

### Test Coverage Highlights:
- **`InvoiceCalculationServiceTest`**: Subtotals, line-level and invoice-level discounts, intra-state (CGST + SGST) vs inter-state (IGST) tax logic, freight taxation, and rounding.
- **`CreditNoteTest`**: Creation, amount validations, original invoice balance adjustment, and credit note lifecycle states.
- **`RequestTest`**: Dynamic root domain routing in production vs subfolder hosting in local development, and URL normalization.
- **`DocumentNumberServiceTest`**: Concurrency-safe financial year sequence generation.

---

## 📁 Directory Structure

```text
toollab-invoice/
├── .env.example                  # Environment configuration template
├── .gitignore                    # Git ignore file
├── .htaccess                     # Apache rewrite and HTTPS redirect rules
├── composer.json                 # Composer dependencies and PSR-4 autoloading
├── phpunit.xml                   # PHPUnit test runner configuration
├── README.md                     # Comprehensive project documentation
├── app/
│   ├── Controllers/              # Auth, Invoices, Estimates, Credit Notes, Payments, Reports
│   ├── Middleware/               # Authentication and CSRF verification
│   ├── Services/                 # Calculation, Numbering, PDF, Export services
│   ├── Support/                  # Router, Request, Response, Database, Config, View helpers
│   └── Views/                    # Invoices, Estimates, Credit Notes, Reports, Layouts
├── bin/
│   └── process-recurring-invoices.php # Recurring invoice CLI generator
├── config/
│   ├── app.php                   # Application settings
│   ├── database.php              # Database connection settings
│   └── constants.php             # GST slabs, payment modes, invoice statuses
├── database/
│   ├── schema.sql                # Complete MySQL DDL schema
│   └── seed.php                  # Database seeder
├── public/
│   ├── index.php                 # Front controller entrypoint
│   ├── favicon.png               # Brand icon
│   ├── logo.png                  # Brand logo
│   ├── sw.js                     # Progressive Web App service worker
│   ├── manifest.json             # PWA web manifest
│   ├── assets/                   # CSS and JavaScript assets
│   └── uploads/                  # User business logos and signatures
├── routes/
│   └── web.php                   # Web application route table
└── tests/
    ├── Unit/                     # Unit test cases
    ├── Integration/              # Integration test cases
    └── bootstrap.php             # Test suite environment bootstrapper
```

---

## 📄 License

This software is released under the [MIT License](LICENSE).
Developed with pride for Indian businesses and global commerce.
