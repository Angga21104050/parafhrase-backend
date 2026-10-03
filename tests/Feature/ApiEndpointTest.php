<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_endpoints(): void
    {
        $registration = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'new-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registration->assertCreated()
            ->assertJsonPath('user.role', 'user')
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $token = $registration->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'new-customer@example.com');

        $login = $this->postJson('/api/login', [
            'email' => 'new-customer@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->withToken($login->json('token'))
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_customer_penjoki_and_admin_endpoints(): void
    {
        Storage::fake();

        $customer = $this->makeUser('user');
        $otherCustomer = $this->makeUser('user');
        $penjoki = $this->makeUser('penjoki');
        $otherPenjoki = $this->makeUser('penjoki');
        $admin = $this->makeUser('admin');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/dashboard')
            ->assertOk();

        $this->getJson('/api/customer/documents')
            ->assertOk()
            ->assertJsonPath('documents.total', 0);

        $upload = $this->postJson('/api/customer/documents', [
            'document' => UploadedFile::fake()->create('source.pdf', 20, 'application/pdf'),
            'customer_note' => 'Tolong parafrase dokumen ini',
        ]);

        $upload->assertCreated();
        $documentId = $upload->json('document.id');

        $this->getJson("/api/customer/documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('document.status', 'PENDING');

        $this->actingAs($otherCustomer, 'sanctum')
            ->getJson("/api/customer/documents/{$documentId}")
            ->assertNotFound();

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/customer/documents/{$documentId}/download-result")
            ->assertStatus(400);

        $this->actingAs($penjoki, 'sanctum')
            ->getJson('/api/penjoki/dashboard')
            ->assertOk();

        $this->getJson('/api/penjoki/available-documents')
            ->assertOk()
            ->assertJsonPath('documents.total', 1);

        $this->postJson("/api/penjoki/documents/{$documentId}/assign")
            ->assertOk()
            ->assertJsonPath('document.status', 'IN_PROGRESS');

        $this->getJson('/api/penjoki/my-documents')
            ->assertOk()
            ->assertJsonPath('documents.total', 1);

        $this->get("/api/penjoki/documents/{$documentId}/download")
            ->assertOk();

        $this->actingAs($otherPenjoki, 'sanctum')
            ->getJson("/api/penjoki/documents/{$documentId}/download")
            ->assertNotFound();

        $this->actingAs($penjoki, 'sanctum')
            ->patchJson("/api/penjoki/documents/{$documentId}/status", [
                'status' => 'COMPLETED',
            ])
            ->assertStatus(400);

        $this->postJson("/api/penjoki/documents/{$documentId}/result", [
            'result' => UploadedFile::fake()->create('result.pdf', 25, 'application/pdf'),
        ])->assertOk()
            ->assertJsonPath('document.status', 'COMPLETED');

        $this->actingAs($customer, 'sanctum')
            ->get("/api/customer/documents/{$documentId}/download-result")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('statistics.total_documents', 1);

        $this->getJson('/api/admin/documents')
            ->assertOk()
            ->assertJsonPath('documents.total', 1);

        $this->getJson("/api/admin/documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('document.id', $documentId);

        $this->patchJson("/api/admin/documents/{$documentId}/status", [
            'status' => 'CANCELLED',
        ])->assertOk()
            ->assertJsonPath('document.status', 'CANCELLED');

        $this->getJson('/api/admin/customers')
            ->assertOk()
            ->assertJsonCount(2, 'customers');

        $this->getJson('/api/admin/penjokis')
            ->assertOk()
            ->assertJsonCount(2, 'penjokis');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_private_endpoints_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/customer/dashboard')->assertUnauthorized();
        $this->getJson('/api/penjoki/dashboard')->assertUnauthorized();
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);
    }
}
