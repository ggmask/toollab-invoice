# Security Architecture & Safeguards

## 1. SQL Injection Prevention
- All database queries without exception utilize **PDO Prepared Statements** with parameter binding (`?` placeholders).
- Dynamic query fragments (such as filters in reports and index pages) strictly construct parameterized SQL strings with bound arrays.
- Raw string interpolation or concatenation into SQL queries is strictly prohibited across the codebase.

---

## 2. Cross-Site Request Forgery (CSRF) Defense
- All state-altering HTTP methods (`POST`, `PUT`, `DELETE`) are protected by the `CsrfMiddleware`.
- Every HTML form renders a hidden `_csrf_token` input generated cryptographically via `bin2hex(random_bytes(32))`.
- Tokens are verified using constant-time string comparison (`hash_equals()`), completely eliminating timing attacks.
- Missing or invalid tokens result in an immediate `419 Page Expired` response.

---

## 3. Session Hardening & Authentication Security
- **Modern Password Hashing**: Passwords are saved using `password_hash($password, PASSWORD_DEFAULT)` and verified with `password_verify()`.
- **Session Cookie Flags**: Configured with `httponly: true` (preventing JavaScript extraction) and `samesite: 'Lax'` (mitigating cross-site cookie leakage).
- **Session Regeneration**: Upon successful login, `session_regenerate_id(true)` is executed to prevent session fixation attacks.

---

## 4. Cross-Site Scripting (XSS) Mitigation
- All user-supplied input rendered in views passes through the global `e()` helper:
  ```php
  function e(?string $value): string {
      return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  ```
- No unescaped user content is printed to HTML output.

---

## 5. CSV & Spreadsheet Formula Injection Protection
- In `ExportService`, any cell value beginning with dangerous formula initiation characters (`=`, `+`, `-`, `@`, `\t`, `\r`) is prefixed with a single quote (`'`).
- This neutralizes Dynamic Data Exchange (DDE) and malicious macro execution when exported reports are opened in Excel.

---

## 6. Multi-Tenant Authorization Guard
- Every controller extracts the active `business_id` from the authenticated session.
- Every SELECT, UPDATE, and DELETE statement includes `AND business_id = ?`.
- Attempting to access an entity belonging to another organization yields a 404 / redirect with no information leakage.
