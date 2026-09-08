<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_check_status_with_valid_number_and_email(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);
        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000010',
            'name' => 'Hendro',
            'email' => 'hendro@example.com',
            'phone' => '6281234567890',
            'department' => 'General Solusindo',
            'position' => 'Marketing',
            'type' => 'leave',
            'leave_date' => '2026-09-25',
            'duration' => 2,
            'status' => 'approved',
            'processed_by' => $hrd->id,
            'processed_at' => now(),
        ]);

        $response = $this->post('/cek-status', [
            'request_number' => 'IZN-2026-000010',
            'email' => 'hendro@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertSee('IZN-2026-000010');
        $response->assertSee('Hendro');
        $response->assertSee('Disetujui');
        $response->assertSee('HRD');

        // Sensitive data check: phone number must NOT be displayed on public status check
        $response->assertDontSee('6281234567890');
    }

    public function test_status_check_with_wrong_email_fails(): void
    {
        LeaveRequest::create([
            'request_number' => 'IZN-2026-000011',
            'name' => 'Intan',
            'email' => 'intan@example.com',
            'phone' => '6281234567891',
            'department' => 'Tabinaco',
            'position' => 'Admin',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'status' => 'pending',
        ]);

        $response = $this->post('/cek-status', [
            'request_number' => 'IZN-2026-000011',
            'email' => 'wrong@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Data Pengajuan Tidak Ditemukan');
        $response->assertDontSee('Intan');
    }
}
