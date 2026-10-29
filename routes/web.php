<?php

use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\EstimateController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\ProductController;
use App\Controllers\RecurringInvoiceController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Support\Request;
use App\Support\Response;
use App\Support\Router;
use App\Support\Session;

// Home redirect
Router::get('/', function (Request $request) {
    Session::start();
    return Session::get('user') ? Response::redirect(url('/dashboard')) : Response::redirect(url('/login'));
});

// Guest Auth Routes
Router::get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
Router::post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, CsrfMiddleware::class]);
Router::get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
Router::post('/register', [AuthController::class, 'register'], [GuestMiddleware::class, CsrfMiddleware::class]);
Router::get('/forgot-password', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
Router::post('/forgot-password', [AuthController::class, 'sendResetLink'], [GuestMiddleware::class, CsrfMiddleware::class]);
Router::post('/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class]);

// Authenticated Application Routes
$auth = [AuthMiddleware::class];
$authWithCsrf = [AuthMiddleware::class, CsrfMiddleware::class];

// 1. Dashboard
Router::get('/dashboard', [DashboardController::class, 'index'], $auth);

// 2. Invoices
Router::get('/invoices', [InvoiceController::class, 'index'], $auth);
Router::get('/invoices/create', [InvoiceController::class, 'create'], $auth);
Router::post('/invoices', [InvoiceController::class, 'store'], $authWithCsrf);
Router::get('/invoices/{id}', [InvoiceController::class, 'show'], $auth);
Router::get('/invoices/{id}/edit', [InvoiceController::class, 'edit'], $auth);
Router::post('/invoices/{id}', [InvoiceController::class, 'update'], $authWithCsrf);
Router::post('/invoices/{id}/issue', [InvoiceController::class, 'issue'], $authWithCsrf);
Router::post('/invoices/{id}/void', [InvoiceController::class, 'void'], $authWithCsrf);
Router::post('/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate'], $authWithCsrf);
Router::get('/invoices/{id}/download', [InvoiceController::class, 'downloadPdf'], $auth);
Router::get('/invoices/{id}/print', [InvoiceController::class, 'printView'], $auth);

// 3. Estimates
Router::get('/estimates', [EstimateController::class, 'index'], $auth);
Router::get('/estimates/create', [EstimateController::class, 'create'], $auth);
Router::post('/estimates', [EstimateController::class, 'store'], $authWithCsrf);
Router::get('/estimates/{id}', [EstimateController::class, 'show'], $auth);
Router::post('/estimates/{id}/status', [EstimateController::class, 'updateStatus'], $authWithCsrf);
Router::post('/estimates/{id}/convert', [EstimateController::class, 'convertToInvoice'], $authWithCsrf);
Router::get('/estimates/{id}/download', [EstimateController::class, 'downloadPdf'], $auth);
Router::get('/estimates/{id}/print', [EstimateController::class, 'printView'], $auth);

// 4. Recurring Invoices
Router::get('/recurring', [RecurringInvoiceController::class, 'index'], $auth);
Router::get('/recurring/create', [RecurringInvoiceController::class, 'create'], $auth);
Router::post('/recurring', [RecurringInvoiceController::class, 'store'], $authWithCsrf);
Router::get('/recurring/{id}', [RecurringInvoiceController::class, 'show'], $auth);
Router::post('/recurring/{id}/toggle', [RecurringInvoiceController::class, 'toggleStatus'], $authWithCsrf);
Router::post('/recurring/{id}/generate', [RecurringInvoiceController::class, 'generateNow'], $authWithCsrf);

// 5. Customers
Router::get('/customers', [CustomerController::class, 'index'], $auth);
Router::get('/customers/create', [CustomerController::class, 'create'], $auth);
Router::post('/customers', [CustomerController::class, 'store'], $authWithCsrf);
Router::get('/customers/{id}', [CustomerController::class, 'show'], $auth);
Router::get('/customers/{id}/edit', [CustomerController::class, 'edit'], $auth);
Router::post('/customers/{id}', [CustomerController::class, 'update'], $authWithCsrf);
Router::post('/customers/{id}/archive', [CustomerController::class, 'archive'], $authWithCsrf);
Router::get('/api/customers/{id}/invoices', [PaymentController::class, 'getCustomerInvoices'], $auth);

// 6. Products & Services
Router::get('/products', [ProductController::class, 'index'], $auth);
Router::get('/products/create', [ProductController::class, 'create'], $auth);
Router::post('/products', [ProductController::class, 'store'], $authWithCsrf);
Router::get('/products/{id}/edit', [ProductController::class, 'edit'], $auth);
Router::post('/products/{id}', [ProductController::class, 'update'], $authWithCsrf);
Router::post('/products/{id}/archive', [ProductController::class, 'archive'], $authWithCsrf);
Router::get('/api/products/search', [ProductController::class, 'apiSearch'], $auth);

// 7. Payments
Router::get('/payments', [PaymentController::class, 'index'], $auth);
Router::get('/payments/create', [PaymentController::class, 'create'], $auth);
Router::post('/payments', [PaymentController::class, 'store'], $authWithCsrf);
Router::get('/payments/{id}', [PaymentController::class, 'show'], $auth);
Router::post('/payments/{id}/reverse', [PaymentController::class, 'reverse'], $authWithCsrf);
Router::get('/payments/{id}/download', [PaymentController::class, 'downloadPdf'], $auth);
Router::get('/payments/{id}/print', [PaymentController::class, 'printView'], $auth);

// 8. Reports
Router::get('/reports', [ReportController::class, 'index'], $auth);
Router::get('/reports/sales', [ReportController::class, 'sales'], $auth);
Router::get('/reports/gst-sales', [ReportController::class, 'gstSales'], $auth);
Router::get('/reports/payments', [ReportController::class, 'payments'], $auth);
Router::get('/reports/receivables', [ReportController::class, 'receivables'], $auth);
Router::get('/reports/customer-statement', [ReportController::class, 'customerStatement'], $auth);
Router::get('/reports/tax-summary', [ReportController::class, 'taxSummary'], $auth);

// 9. Settings
Router::get('/settings/business', [SettingsController::class, 'business'], $auth);
Router::post('/settings/business', [SettingsController::class, 'updateBusiness'], $authWithCsrf);
Router::get('/settings/gst', [SettingsController::class, 'gst'], $auth);
Router::post('/settings/gst', [SettingsController::class, 'updateGst'], $authWithCsrf);
Router::get('/settings/invoice', [SettingsController::class, 'invoice'], $auth);
Router::post('/settings/invoice', [SettingsController::class, 'updateInvoice'], $authWithCsrf);
Router::get('/settings/tax-rates', [SettingsController::class, 'taxRates'], $auth);
Router::post('/settings/tax-rates', [SettingsController::class, 'storeTaxRate'], $authWithCsrf);
Router::get('/settings/account', [SettingsController::class, 'account'], $auth);
Router::post('/settings/account', [SettingsController::class, 'updateAccount'], $authWithCsrf);
