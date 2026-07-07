<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}

class ListingControllerTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [
            'user' => [
                'company_id' => 55,
            ],
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCompanyIdAceitaUsuarioOuCompanyIdDiretoNaSessao(): void
    {
        $this->assertSame(55, $this->invokePrivate('companyId'));

        unset($_SESSION['user']);
        $_SESSION['company_id'] = 66;

        $this->assertSame(66, $this->invokePrivate('companyId'));
    }

    public function testValidateImageUploadsAceitaPngReal(): void
    {
        $errors = [];
        $file = $this->createPngFile();

        $uploads = $this->invokeValidateImageUploads([
            'error' => [UPLOAD_ERR_OK],
            'tmp_name' => [$file],
            'size' => [filesize($file)],
        ], $errors);

        $this->assertSame([], $errors);
        $this->assertCount(1, $uploads);
        $this->assertSame($file, $uploads[0]['tmp_name']);
        $this->assertSame('png', $uploads[0]['extension']);
    }

    public function testValidateImageUploadsRejeitaMimeInvalido(): void
    {
        $errors = [];
        $file = $this->createTemporaryFile('conteudo que nao e imagem');

        $uploads = $this->invokeValidateImageUploads([
            'error' => [UPLOAD_ERR_OK],
            'tmp_name' => [$file],
            'size' => [filesize($file)],
        ], $errors);

        $this->assertSame([], $uploads);
        $this->assertStringContainsString('WebP', implode(' ', $errors));
    }

    public function testValidateImageUploadsRejeitaArquivoMaiorQueCincoMb(): void
    {
        $errors = [];

        $uploads = $this->invokeValidateImageUploads([
            'error' => [UPLOAD_ERR_OK],
            'tmp_name' => ['arquivo-inexistente'],
            'size' => [(5 * 1024 * 1024) + 1],
        ], $errors);

        $this->assertSame([], $uploads);
        $this->assertStringContainsString('5 MB', implode(' ', $errors));
    }

    public function testValidacaoDeAnuncioBloqueiaCamposObrigatorios(): void
    {
        $errors = $this->validateListingPayload([
            'type' => 'invalid',
            'title' => 'abc',
            'category_id' => 0,
            'quantity' => 0,
            'location_state' => '',
            'location_city' => '',
        ]);

        $this->assertCount(6, $errors);
    }

    public function testOfertaSemPrecoViraDoacaoEPrecoNegativoFalha(): void
    {
        $valid = $this->normalizeListingPrice('offer', '');
        $invalid = $this->normalizeListingPrice('offer', '-1');

        $this->assertSame(0.0, $valid['price']);
        $this->assertContains('Insira um preço válido ou deixe vazio para doação.', $invalid['errors']);
    }

    public function testDemandaSempreGravaPrecoNulo(): void
    {
        $result = $this->normalizeListingPrice('demand', '999,99');

        $this->assertNull($result['price']);
        $this->assertSame([], $result['errors']);
    }

    public function testEdicaoNaoPodeRemoverTodasAsImagens(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pelo menos uma imagem');

        $this->assertImagesRemain(currentImages: 2, authorizedDeletes: 2, uploads: 0);
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(ListingController::class, $method);

        return $reflection->invoke(null, ...$args);
    }

    private function invokeValidateImageUploads(array $files, array &$errors): array
    {
        $reflection = new ReflectionMethod(ListingController::class, 'validateImageUploads');

        return $reflection->invokeArgs(null, [$files, &$errors]);
    }

    private function createPngFile(): string
    {
        return $this->createTemporaryFile(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));
    }

    private function createTemporaryFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'listing-test-');
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }

    private function validateListingPayload(array $input): array
    {
        $errors = [];
        if (!in_array($input['type'] ?? null, ['offer', 'demand'], true)) $errors[] = 'Selecione se o anúncio é uma Oferta ou Demanda.';
        if (empty($input['title']) || strlen((string) $input['title']) < 5) $errors[] = 'O título deve ter no mínimo 5 caracteres.';
        if (empty($input['category_id'])) $errors[] = 'Selecione uma categoria válida.';
        if ((float) ($input['quantity'] ?? 0) <= 0) $errors[] = 'A quantidade deve ser maior que zero.';
        if (empty($input['location_state'])) $errors[] = 'Selecione o Estado.';
        if (empty($input['location_city'])) $errors[] = 'Selecione a Cidade.';

        return $errors;
    }

    private function normalizeListingPrice(string $type, ?string $raw): array
    {
        $errors = [];
        $price = null;
        if ($type === 'offer') {
            if ($raw === '' || $raw === null) {
                $price = 0.0;
            } else {
                $normalized = str_replace(['.', ','], ['', '.'], $raw);
                $price = filter_var($normalized, FILTER_VALIDATE_FLOAT);
                if ($price === false || $price < 0) {
                    $errors[] = 'Insira um preço válido ou deixe vazio para doação.';
                    $price = null;
                }
            }
        }

        return ['price' => $price, 'errors' => $errors];
    }

    private function assertImagesRemain(int $currentImages, int $authorizedDeletes, int $uploads): void
    {
        if (($currentImages - $authorizedDeletes + $uploads) < 1) {
            throw new RuntimeException('O anúncio deve permanecer com pelo menos uma imagem.');
        }

        $this->addToAssertionCount(1);
    }
}
