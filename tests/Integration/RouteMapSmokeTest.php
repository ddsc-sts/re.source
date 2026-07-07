<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RouteMapSmokeTest extends TestCase
{
    public function testRotasCriticasApontamParaOsControllersEsperados(): void
    {
        $routes = require __DIR__ . '/../../routes/web.php';

        $this->assertSame(['HomeController', 'index'], $routes['/']['action']);
        $this->assertSame(['AuthController', 'login'], $routes['/login']['action']);
        $this->assertSame(['AuthController', 'pendente'], $routes['/pendente']['action']);
        $this->assertSame(['ChatController', 'index'], $routes['/conversas']['action']);
        $this->assertSame(['NegotiationController', 'start'], $routes['/conversas/iniciar']['action']);
        $this->assertSame(['FreightController', 'quote'], $routes['/frete']['action']);
        $this->assertSame(['DeliveryController', 'portal'], $routes['/admin/logistica']['action']);
        $this->assertSame(['BaseController', 'impacto'], $routes['/impacto']['action']);
        $this->assertSame(['BaseController', 'suporte'], $routes['/suporte']['action']);
    }

    public function testRotasProtegidasCarregamMiddlewaresEsperados(): void
    {
        $routes = require __DIR__ . '/../../routes/web.php';

        $this->assertContains(['UserAuth', 'required'], $routes['/base']['middleware']);
        $this->assertContains(['ApprovedCompany', 'required'], $routes['/estatisticas']['middleware']);
        $this->assertContains(['AdminAuth', 'required'], $routes['/admin']['middleware']);
        $this->assertContains(['AdminAuth', 'required'], $routes['/admin/saques']['middleware']);
    }
}
