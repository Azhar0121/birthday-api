<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_full_api_lifecycle(): void
    {
        Storage::fake('public');

        // 1. Register User
        $registerRes = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice',
            'email' => 'alice@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $registerRes->assertStatus(201);
        $token = $registerRes->json('data.token');
        $this->assertNotEmpty($token);

        // 2. Get Templates
        $templatesRes = $this->getJson('/api/v1/templates');
        $templatesRes->assertStatus(200)
            ->assertJsonPath('data.data.0.code', 'TMP-001');

        $templateId = $templatesRes->json('data.data.0.id');

        // 3. Create Draft Project
        $createProjectRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/projects', [
                'template_id' => $templateId,
                'title' => "Nadia's 20th Birthday Surprise",
                'recipient_name' => 'Nadia Aulia Fatimah',
                'age' => 20,
                'short_message' => 'Happy 20th Birthday!',
                'letter_content' => 'Dear Nadia, Happy Birthday...',
            ]);

        $createProjectRes->assertStatus(201);
        $projectId = $createProjectRes->json('data.id');
        $slug = $createProjectRes->json('data.slug');

        // 4. Upload Photo Media
        $file = UploadedFile::fake()->image('photo01.jpg', 600, 800);
        $uploadRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/projects/{$projectId}/media", [
                'slot_key' => 'photo_01',
                'type' => 'photo',
                'file' => $file,
                'title' => 'A Beautiful Beginning',
                'caption' => 'First memory together',
            ]);

        $uploadRes->assertStatus(200)
            ->assertJsonPath('data.slot_key', 'photo_01');

        // 5. Publish Project
        $publishRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/projects/{$projectId}/publish");

        $publishRes->assertStatus(200)
            ->assertJsonPath('data.project.status', 'PUBLISHED');

        // 6. Access Public Greeting Without Auth
        $publicRes = $this->getJson("/api/v1/g/{$slug}");
        $publicRes->assertStatus(200)
            ->assertJsonPath('data.recipient_name', 'Nadia Aulia Fatimah')
            ->assertJsonPath('data.age', 20)
            ->assertJsonPath('data.media.photos.0.slot_key', 'photo_01');
    }
}
