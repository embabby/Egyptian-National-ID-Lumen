<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class NationalIdApiTest extends TestCase
{
    use DatabaseMigrations;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $plain = 'test_key_' . bin2hex(random_bytes(8));
        ApiKey::create([
            'name' => 'Test Key',
            'key_hash' => hash('sha256', $plain),
            'is_active' => true,
        ]);
        $this->apiKey = $plain;
    }

    public function test_health_endpoint_returns_ok_without_auth(): void
    {
        $response = $this->get('/api/v1/health');
        $response->seeStatusCode(200);
        $response->seeJson(['status' => 'ok']);
    }

    public function test_validate_requires_api_key(): void
    {
        $response = $this->post('/api/v1/national-id/validate', ['national_id' => '29001011234567']);
        $response->seeStatusCode(401);
        $response->seeJsonStructure(['message']);
    }

    public function test_validate_requires_national_id(): void
    {
        $response = $this->post('/api/v1/national-id/validate', [], [
            'X-API-Key' => $this->apiKey,
        ]);
        $response->seeStatusCode(422);
        $response->seeJsonStructure(['errors']);
    }

    public function test_validate_rejects_invalid_length(): void
    {
        $response = $this->post('/api/v1/national-id/validate', ['national_id' => '12345'], [
            'X-API-Key' => $this->apiKey,
        ]);
        $response->seeStatusCode(422);
    }

    public function test_validate_returns_valid_false_for_invalid_id(): void
    {
        $response = $this->post('/api/v1/national-id/validate', ['national_id' => '00000000000000'], [
            'X-API-Key' => $this->apiKey,
        ]);
        $response->seeStatusCode(200);
        $response->seeJson(['valid' => false]);
        $response->seeJson(['national_id' => '00000000000000']);
        $data = $response->response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertNull($data['data']);
    }

    public function test_validate_returns_extracted_data_for_valid_id(): void
    {
        $validId = $this->validEgyptianId();
        $response = $this->post('/api/v1/national-id/validate', ['national_id' => $validId], [
            'X-API-Key' => $this->apiKey,
        ]);
        $response->seeStatusCode(200);
        $response->seeJson(['valid' => true, 'national_id' => $validId]);
        $data = $response->response->getData(true);
        $this->assertNotNull($data['data']);
        $this->assertArrayHasKey('birth_date', $data['data']);
        $this->assertArrayHasKey('birth_year', $data['data']);
        $this->assertArrayHasKey('gender', $data['data']);
        $this->assertArrayHasKey('governorate', $data['data']);
    }

    public function test_accepts_bearer_token(): void
    {
        $validId = $this->validEgyptianId();
        $response = $this->get('/api/v1/national-id/validate?national_id=' . $validId, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);
        $response->seeStatusCode(200);
        $response->seeJson(['valid' => true]);
    }

    private function validEgyptianId(): string
    {
        $prefix = '2950620211001';
        $digits = array_map('intval', str_split($prefix));
        $sum = 0;
        $len = count($digits);
        for ($i = 0; $i < $len; $i++) {
            $d = $digits[$i];
            if (($len - 1 - $i) % 2 === 0) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
        }
        $check = (10 - ($sum % 10)) % 10;

        return $prefix . $check;
    }
}
