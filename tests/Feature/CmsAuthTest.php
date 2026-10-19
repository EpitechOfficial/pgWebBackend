<?php

namespace Tests\Feature;

use Tests\TestCase;

class CmsAuthTest extends TestCase
{
    public function test_cms_routes_require_authentication(): void
    {
        $this->getJson('/api/cms/me')
            ->assertUnauthorized()
            ->assertExactJson(['status' => 'error', 'message' => 'Unauthenticated.']);

        $this->getJson('/api/cms/home')->assertUnauthorized();
    }

    public function test_authenticated_session_returns_current_user(): void
    {
        $this->withSession(['cms_user' => 'administrator'])
            ->getJson('/api/cms/me')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'administrator');
    }

    public function test_logout_invalidates_the_cms_session(): void
    {
        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->getJson('/api/cms/me')->assertUnauthorized();
    }

    public function test_login_requires_both_credentials(): void
    {
        $this->postJson('/api/cms/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Username and password are required.');
    }

    public function test_cms_write_routes_require_authentication(): void
    {
        $this->postJson('/api/cms/contact', [])->assertUnauthorized();
        $this->postJson('/api/cms/academics/programmes/toggle', [])->assertUnauthorized();
    }

    public function test_cms_write_validation_preserves_legacy_messages(): void
    {
        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/contact', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Phone, emails, and all social links are required.');

        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/academics/programmes', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'All programme fields are required.');
    }

    public function test_document_upload_requires_a_supported_file(): void
    {
        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/forms/upload')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No file was uploaded or there was an error uploading the file.');
    }

    public function test_image_mutations_preserve_legacy_validation_messages(): void
    {
        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/home/carousels', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Input image url is required.');

        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/home/flagship', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Programme, title, and introduction are required.');

        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/academics/facilities', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Department, header, and content are required.');

        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/academics/supervisors', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Department, name, title, link, and about are required.');

        $this->withSession(['cms_user' => 'administrator'])
            ->postJson('/api/cms/academics/students', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Quote and content are required.');
    }

    public function test_api_cors_allows_the_configured_frontend_with_credentials(): void
    {
        $this->withHeader('Origin', 'http://localhost:5175')
            ->getJson('/api/health')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5175')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}
