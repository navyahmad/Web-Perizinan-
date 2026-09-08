<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_reports(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);

        LeaveRequest::create([
            'request_number' => 'IZN-2026-000020',
            'name' => 'Joko',
            'email' => 'joko@example.com',
            'phone' => '6281234567890',
            'department' => 'General Solusindo',
            'position' => 'Staff',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($hrd)->get('/laporan');
        $response->assertStatus(200);
        $response->assertSee('Laporan Izin Kantor');
        $response->assertSee('General Solusindo');
    }

    public function test_can_export_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        LeaveRequest::create([
            'request_number' => 'IZN-2026-000021',
            'name' => 'Kiki',
            'email' => 'kiki@example.com',
            'phone' => '6281234567890',
            'department' => 'Tabinaco',
            'position' => 'Staff',
            'type' => 'leave',
            'leave_date' => '2026-09-20',
            'duration' => 3,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/laporan/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('IZN-2026-000021', $response->streamedContent());
        $this->assertStringContainsString('Kiki', $response->streamedContent());
    }
}
