# Financial Reporting & Export Engine

## 1. Overview

ToolLab Invoice includes six specialized reporting engines designed to satisfy Indian tax filing, corporate auditing, and management accounting needs.

---

## 2. Report Specifications

### 1. Sales Register (`/reports/sales`)
- **Objective**: Complete register of outward supplies for any chosen date window.
- **Columns**: Invoice #, Issue Date, Due Date, Customer Name, Recipient GSTIN, Taxable Value, CGST, SGST, IGST, Gross Invoiced Amount, Realized Amount, Balance Due, Status.
- **Summary Metrics**: Taxable Sales Turnover, Total Taxes Collected, Gross Invoiced Turnover, Total Collections Realized, Outstanding Receivables.

### 2. GSTR-1 Ready Sales Register (`/reports/gst-sales`)
- **Objective**: Itemized line-by-line GST return filing data.
- **Classification**:
  - **B2B (Table 4)**: Registered business customers with valid GSTIN.
  - **B2C Large (Table 5)**: Inter-State supplies to unregistered persons exceeding ₹2.5 Lakh.
  - **B2C Small (Table 7)**: Intra-State supplies to unregistered persons or Inter-State supplies $\le$ ₹2.5 Lakh.
- **Columns**: Invoice #, Date, B2B/B2C, Customer, Recipient GSTIN, Place of Supply with 2-digit code, Reverse Charge flag, HSN/SAC, Item Description, Quantity, Unit, Taxable Value, GST Rate %, CGST, SGST, IGST, Line Total.

### 3. GST Tax Liability Summary (`/reports/tax-summary`)
- **Objective**: Aggregated tax output liabilities categorized by GST tax slab for preparing **Table 3.1 of GSTR-3B**.
- **Grouping**: Slabs (0%, 5%, 12%, 18%, 28%).
- **Columns**: Tax Slab %, Invoices Count, Taxable Base, CGST, SGST, IGST, Cess, Total Tax Liability, Gross Turnover.

### 4. Outstanding Receivables & Ageing Analysis (`/reports/receivables`)
- **Objective**: Liquidity management and credit risk monitoring.
- **Ageing Brackets**:
  1. **Not Yet Due**: Invoices within allowed credit period.
  2. **1–30 Days Overdue**: Early reminder bracket.
  3. **31–60 Days Overdue**: Escalation bracket.
  4. **61–90 Days Overdue**: Serious delinquency bracket.
  5. **90+ Days Overdue**: High-risk debt bracket.
- **Follow-up Tooling**: Displays customer contact numbers for direct phone follow-ups and one-click **Collect** shortcuts.

### 5. Customer Statement of Account (`/reports/customer-statement`)
- **Objective**: Detailed customer ledger for account reconciliation and audit confirmation.
- **Columns**: Date, Transaction Type (Sales Invoice / Payment Receipt), Reference #, Description, Debit (Invoice amount), Credit (Payment received), Running Account Balance.

---

## 3. High-Fidelity Export Architecture

ToolLab Invoice provides native multi-format exports without relying on external dependencies or C-extensions like `ext-zip`:

### 1. RFC-4180 Compliant CSV with UTF-8 BOM
- Prepends the 3-byte UTF-8 Byte Order Mark (`\xEF\xBB\xBF`).
- Ensures Microsoft Excel on Windows immediately opens the file in UTF-8 mode without requiring the user to import text or change codepage settings.
- Correctly formats quotes, commas, and line breaks.

### 2. Microsoft XML SpreadsheetML (`.xls`)
- Generates valid XML Spreadsheet 2003 specification supported across all versions of Excel, LibreOffice, and Google Sheets.
- **Explicit Cell Typing**: Numeric currency amounts are explicitly marked as `<Data ss:Type="Number">`, enabling instant calculations and formulas in Excel.
- **Formula Injection Defense**: Neutralizes dangerous leading characters (`=`, `+`, `-`, `@`) in text fields to prevent CSV/Excel DDE injection attacks.
- **Styling**: Pre-styles table headers with bold typography, custom background fills, and borders.
