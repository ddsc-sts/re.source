<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}

class MelhorEnvioServiceIntegrationTest extends TestCase
{
    private static ?int $port = null;
    private static ?string $routerFile = null;
    private static $serverProcess = null;

    public static function setUpBeforeClass(): void
    {
        [$port, $routerFile] = self::startMockServer();
        self::$port = $port;
        self::$routerFile = $routerFile;
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }
        if (self::$routerFile && is_file(self::$routerFile)) {
            unlink(self::$routerFile);
        }
    }

    public function testQuoteRetornaRespostaDoServidorLocal(): void
    {
        $service = $this->serviceWithBaseUrl(true);
        $result = $service->quote(['from' => '01001000', 'to' => '20000000']);

        $this->assertSame('calculate', $result['endpoint']);
        $this->assertSame('Bearer test-token', $result['headers']['Authorization'] ?? null);
    }

    public function testQuoteSemTokenRecebe401DoServidorLocal(): void
    {
        $service = $this->serviceWithBaseUrl(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Token ausente');

        $service->quote(['from' => '01001000']);
    }

    public function testEndpointTrackingEscapaCaracteresEspeciais(): void
    {
        $service = $this->serviceWithBaseUrl(true);
        $result = $service->tracking('ABC/123');

        $this->assertSame('/api/v2/me/shipment/tracking/ABC%2F123', $result['requested_uri']);
    }

    public function testHttpErrorPropagaMensagemDaApi(): void
    {
        $service = $this->serviceWithBaseUrl(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API recusou o payload');

        $service->contract(['mode' => 'error']);
    }

    public function testCurlFailureLancaRuntimeException(): void
    {
        $service = new MelhorEnvioService();
        $this->setPrivateProperty($service, 'baseUrl', 'http://127.0.0.1:1');
        $this->setPrivateProperty($service, 'accessToken', 'test-token');

        $this->expectException(RuntimeException::class);

        $service->label(10);
    }

    private function serviceWithBaseUrl(bool $withToken): MelhorEnvioService
    {
        $service = new MelhorEnvioService();
        $this->setPrivateProperty($service, 'baseUrl', 'http://127.0.0.1:' . self::$port);
        $this->setPrivateProperty($service, 'accessToken', $withToken ? 'test-token' : null);

        return $service;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    private static function startMockServer(): array
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$socket) {
            throw new RuntimeException('Não foi possível reservar porta local: ' . $errstr);
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $port = (int) substr(strrchr($name, ':'), 1);

        $routerFile = tempnam(sys_get_temp_dir(), 'melhor-envio-router-') . '.php';
        file_put_contents($routerFile, <<<'PHP'
<?php
$headers = function_exists('getallheaders') ? getallheaders() : [];
$uri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = json_decode(file_get_contents('php://input'), true) ?: [];

header('Content-Type: application/json; charset=utf-8');

if (strpos($uri, '/error') !== false || (($body['mode'] ?? '') === 'error')) {
    http_response_code(422);
    echo json_encode(['message' => 'API recusou o payload'], JSON_UNESCAPED_UNICODE);
    return;
}

if (empty($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Token ausente'], JSON_UNESCAPED_UNICODE);
    return;
}

echo json_encode([
    'endpoint' => basename($uri),
    'requested_uri' => $uri,
    'method' => $method,
    'headers' => $headers,
    'body' => $body,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
PHP
        );

        $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'melhor-envio-test.log';
        $cmd = [
            'C:\\xampp\\php\\php.exe',
            '-S',
            '127.0.0.1:' . $port,
            '-t',
            dirname($routerFile),
            $routerFile,
        ];
        self::$serverProcess = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ], $pipes);

        if (is_resource(self::$serverProcess) && isset($pipes[0]) && is_resource($pipes[0])) {
            fclose($pipes[0]);
        }

        $started = false;
        for ($i = 0; $i < 50; $i++) {
            usleep(100000);
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            $probe = @file_get_contents('http://127.0.0.1:' . $port . '/', false, $context);
            if ($probe !== false || !empty($http_response_header)) {
                $started = true;
                break;
            }
        }

        if (!$started) {
            if (is_file($logFile)) {
                error_log((string) file_get_contents($logFile));
            }
            throw new RuntimeException('Servidor local de teste não iniciou.');
        }

        return [$port, $routerFile];
    }
}
