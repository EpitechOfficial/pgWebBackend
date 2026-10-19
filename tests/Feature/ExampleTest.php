<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_api_index_returns_a_successful_response(): void
    {
        $response = $this->get('/api');

        $response->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_validation_errors_keep_the_api_response_shape(): void
    {
        $this->getJson('/api/site/research-areas/options')
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error');
    }

    public function test_file_backed_public_endpoints_keep_their_contract(): void
    {
        $this->getJson('/api-external-links.php')->assertOk()->assertJsonPath('status', 'ok');
        $this->getJson('/api-sectional-heads.php')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_file_backed_updates_require_cms_authentication(): void
    {
        $this->postJson('/api-external-links.php', [])->assertUnauthorized();
        $this->postJson('/api-sectional-heads.php', [])->assertUnauthorized();
    }
}
