<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\Request;
use App\Support\Config;

final class RequestTest extends TestCase
{
    private string $originalAppUrl;

    protected function setUp(): void
    {
        $this->originalAppUrl = Config::get('app.url', '');
    }

    protected function tearDown(): void
    {
        Config::set('app.url', $this->originalAppUrl);
    }

    public function testRootDomainRoutingInProduction(): void
    {
        // Simulate production root domain without subfolder
        Config::set('app.url', 'https://invoicing.production.com');

        $_SERVER['REQUEST_URI'] = '/dashboard';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $req = new Request();
        $this->assertEquals('/dashboard', $req->path());
        $this->assertEquals('https://invoicing.production.com', $req->baseUrl());
    }

    public function testSubfolderRoutingInDevelopment(): void
    {
        // Simulate local subfolder hosting
        Config::set('app.url', 'https://localhost/toollab-invoice');

        $_SERVER['REQUEST_URI'] = '/toollab-invoice/invoices/create';
        $_SERVER['SCRIPT_NAME'] = '/toollab-invoice/public/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $req = new Request();
        $this->assertEquals('/invoices/create', $req->path());
        $this->assertEquals('https://localhost/toollab-invoice', $req->baseUrl());
    }

    public function testLegacyPublicPathStripping(): void
    {
        Config::set('app.url', 'https://localhost/toollab-invoice');

        $_SERVER['REQUEST_URI'] = '/toollab-invoice/public/login';
        $_SERVER['SCRIPT_NAME'] = '/toollab-invoice/public/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $req = new Request();
        $this->assertEquals('/login', $req->path());
    }
}
