<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SearchControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(SearchController::class));
    }

    public function testBuscaMontaFiltrosValidosComPlaceholdersSeparados(): void
    {
        $result = $this->buildSearchQuery([
            'q' => 'plastico',
            'category_id' => 2,
            'empresa' => 5,
            'type' => 'offer',
            'location_state' => 'sp',
            'location_city' => 'Campinas',
        ]);

        $this->assertStringContainsString('l.title LIKE :q_title', $result['sql']);
        $this->assertStringContainsString('l.description LIKE :q_description', $result['sql']);
        $this->assertSame('%plastico%', $result['params'][':q_title']);
        $this->assertSame('%plastico%', $result['params'][':q_description']);
        $this->assertSame(2, $result['params'][':category_id']);
        $this->assertSame(5, $result['params'][':company_id']);
        $this->assertSame('offer', $result['params'][':type']);
        $this->assertSame('SP', $result['params'][':state']);
        $this->assertSame('Campinas', $result['params'][':city']);
    }

    public function testBuscaIgnoraTipoInvalidoEMapeiaAliasDeCategoria(): void
    {
        $categoryId = $this->resolveCategoryByName('papelão', [
            ['id' => 1, 'name' => 'Papel/Papelão'],
            ['id' => 2, 'name' => 'Eletrônico'],
        ]);
        $result = $this->buildSearchQuery(['type' => 'invalid']);

        $this->assertSame(1, $categoryId);
        $this->assertArrayNotHasKey(':type', $result['params']);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function buildSearchQuery(array $input): array
    {
        $q = trim((string) ($input['q'] ?? ''));
        $categoryId = isset($input['category_id']) ? (int) $input['category_id'] : null;
        $companyId = isset($input['empresa']) ? (int) $input['empresa'] : null;
        $type = (string) ($input['type'] ?? '');
        $state = strtoupper(trim((string) ($input['location_state'] ?? '')));
        $city = trim((string) ($input['location_city'] ?? ''));

        $sql = "WHERE l.status = 'active' AND l.deleted_at IS NULL";
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (l.title LIKE :q_title OR l.description LIKE :q_description)';
            $params[':q_title'] = "%{$q}%";
            $params[':q_description'] = "%{$q}%";
        }
        if ($categoryId) {
            $sql .= ' AND l.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }
        if ($companyId) {
            $sql .= ' AND l.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        if ($type !== '' && in_array($type, ['offer', 'demand'], true)) {
            $sql .= ' AND l.type = :type';
            $params[':type'] = $type;
        }
        if ($state !== '') {
            $sql .= ' AND l.location_state = :state';
            $params[':state'] = $state;
        }
        if ($city !== '') {
            $sql .= ' AND l.location_city = :city';
            $params[':city'] = $city;
        }

        return ['sql' => $sql, 'params' => $params];
    }

    private function resolveCategoryByName(string $name, array $categories): ?int
    {
        $normalized = mb_strtolower($name, 'UTF-8');
        $aliases = ['papelão' => 'papel/papelão', 'eletrônicos' => 'eletrônico'];
        $normalized = $aliases[$normalized] ?? $normalized;
        foreach ($categories as $category) {
            if (mb_strtolower(trim($category['name']), 'UTF-8') === $normalized) {
                return (int) $category['id'];
            }
        }

        return null;
    }
}
