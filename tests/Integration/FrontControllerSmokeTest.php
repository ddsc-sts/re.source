<?php

declare(strict_types=1);

require_once __DIR__ . '/../Support/MySqlTestDatabase.php';

use PHPUnit\Framework\TestCase;

class FrontControllerSmokeTest extends TestCase
{
    private MySqlTestDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new MySqlTestDatabase();
        $this->db->installRouteSmokeSchema();
        $this->seedSmokeData();
    }

    protected function tearDown(): void
    {
        $this->db->drop();
        parent::tearDown();
    }

    public function testPaginaInicialELoginCarregamPeloFrontController(): void
    {
        $home = $this->runFrontController('/re.source/');
        $login = $this->runFrontController('/re.source/login');
        $pendente = $this->runFrontController('/re.source/pendente');

        $this->assertStringContainsString('Re.Source', $home);
        $this->assertStringContainsString('Entrar', $login);
        $this->assertStringContainsString('Confirme seu e-mail', $pendente);
    }

    public function testAguardandoAprovacaoEConversaCarregamComSessaoReal(): void
    {
        $pendingSession = [
            'user' => [
                'id' => 1,
                'company_id' => 1,
                'role' => 'admin_company',
            ],
        ];

        $activeSession = [
            'user' => [
                'id' => 2,
                'company_id' => 2,
                'role' => 'admin_company',
            ],
        ];

        $awaiting = $this->runFrontController('/re.source/aguardando-aprovacao', $pendingSession);
        $chat = $this->runFrontController('/re.source/conversas', $activeSession);

        $this->assertStringContainsString('Cadastro recebido', $awaiting);
        $this->assertStringContainsString('Suas conversas', $chat);
    }

    private function seedSmokeData(): void
    {
        $this->db->pdo()->exec("INSERT INTO companies (id, razao_social, nome_fantasia, status, review_notes) VALUES (1, 'Demo LTDA', 'Demo', 'pending', 'Revise o CNPJ')");
        $this->db->pdo()->exec("INSERT INTO companies (id, razao_social, nome_fantasia, status) VALUES (2, 'Ativa LTDA', 'Ativa', 'active')");
        $this->db->pdo()->exec("INSERT INTO users (id, company_id, name, email, role) VALUES (1, 1, 'Admin Demo', 'demo@example.com', 'admin_company')");
        $this->db->pdo()->exec("INSERT INTO users (id, company_id, name, email, role) VALUES (2, 2, 'Admin Ativa', 'ativa@example.com', 'admin_company')");
        $this->db->pdo()->exec("INSERT INTO listings (id, company_id, title, type, unit, status) VALUES (1, 2, 'Sucata', 'offer', 'kg', 'active')");
        $this->db->pdo()->exec("INSERT INTO negotiations (id, listing_id, buyer_company_id, seller_company_id, status) VALUES (1, 1, 2, 1, 'open')");
    }

    private function runFrontController(string $requestUri, array $session = [], string $method = 'GET'): string
    {
        $runner = tempnam(sys_get_temp_dir(), 're-source-front-') . '.php';
        $dbName = $this->dbName();
        $runnerCode = <<<'PHP'
<?php
session_start();
$_SESSION = json_decode($argv[1], true) ?: [];
$_SERVER['REQUEST_URI'] = $argv[2];
$_SERVER['REQUEST_METHOD'] = $argv[3] ?? 'GET';
$_SERVER['HTTP_HOST'] = '127.0.0.1';
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');
putenv('DB_DATABASE=__DB_NAME__');
putenv('APP_BASE_PATH=/re.source');
putenv('APP_DEBUG=true');
$GLOBALS['_ENV']['DB_HOST'] = '127.0.0.1';
$GLOBALS['_ENV']['DB_PORT'] = '3306';
$GLOBALS['_ENV']['DB_USERNAME'] = 'root';
$GLOBALS['_ENV']['DB_PASSWORD'] = '';
$GLOBALS['_ENV']['DB_DATABASE'] = '__DB_NAME__';
$GLOBALS['_ENV']['APP_BASE_PATH'] = '/re.source';
$GLOBALS['_ENV']['APP_DEBUG'] = 'true';
ob_start();
require 'C:/xampp/htdocs/re.source/public/index.php';
echo ob_get_clean();
PHP;
        file_put_contents($runner, str_replace('__DB_NAME__', $dbName, $runnerCode));

        $cmd = ['C:\\xampp\\php\\php.exe', $runner, json_encode($session, JSON_UNESCAPED_UNICODE), $requestUri, $method];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            unlink($runner);
            $this->fail('Não foi possível iniciar o processo do front controller.');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        unlink($runner);

        $this->assertSame(0, $code, $stderr);

        return $stdout;
    }

    private function dbName(): string
    {
        $ref = new ReflectionProperty($this->db, 'databaseName');
        $ref->setAccessible(true);

        return (string) $ref->getValue($this->db);
    }
}
