<?php

namespace Tests\Feature;

use App\Mail\LeaveRequestApprovedMail;
use App\Mail\LeaveRequestRejectedMail;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_hrd_can_approve_pending_request_and_email_is_sent(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);
        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000001',
            'name' => 'Karyawan A',
            'email' => 'karyawan.a@example.com',
            'phone' => '6281234567890',
            'department' => 'General Solusindo',
            'position' => 'Staff',
            'type' => 'leave',
            'leave_date' => '2026-09-20',
            'duration' => 2,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($hrd)->post("/pengajuan/{$leave->id}/approve", [
            'approval_note' => 'Silakan, selamat berlibur.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($hrd->id, $leave->processed_by);
        $this->assertNotNull($leave->processed_at);
        $this->assertEquals('Silakan, selamat berlibur.', $leave->approval_note);
        $this->assertEquals('sent', $leave->email_status);

        Mail::assertSent(LeaveRequestApprovedMail::class, function ($mail) {
            return $mail->hasTo('karyawan.a@example.com');
        });
    }

    public function test_admin_can_approve_pending_request_directly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000002',
            'name' => 'Karyawan B',
            'email' => 'karyawan.b@example.com',
            'phone' => '6281234567891',
            'department' => 'Tabinaco',
            'position' => 'Staff',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/pengajuan/{$leave->id}/approve");

        $response->assertSessionHas('success');
        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($admin->id, $leave->processed_by);
    }

    public function test_hrd_can_reject_with_mandatory_reason(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);
        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000003',
            'name' => 'Karyawan C',
            'email' => 'karyawan.c@example.com',
            'phone' => '6281234567892',
            'department' => 'General Solusindo',
            'position' => 'Staff',
            'type' => 'personal',
            'leave_date' => '2026-09-10',
            'status' => 'pending',
        ]);

        // Reject tanpa alasan -> gagal validasi
        $responseNoReason = $this->actingAs($hrd)->from("/pengajuan/{$leave->id}")->post("/pengajuan/{$leave->id}/reject", [
            'rejection_reason' => '',
        ]);
        $responseNoReason->assertSessionHasErrors('rejection_reason');

        // Reject dengan alasan -> berhasil
        $responseWithReason = $this->actingAs($hrd)->post("/pengajuan/{$leave->id}/reject", [
            'rejection_reason' => 'Ada meeting penting dengan klien pada tanggal tersebut.',
        ]);

        $responseWithReason->assertSessionHas('success');
        $leave->refresh();
        $this->assertEquals('rejected', $leave->status);
        $this->assertEquals('Ada meeting penting dengan klien pada tanggal tersebut.', $leave->rejection_reason);
        $this->assertEquals($hrd->id, $leave->processed_by);

        Mail::assertSent(LeaveRequestRejectedMail::class, function ($mail) {
            return $mail->hasTo('karyawan.c@example.com');
        });
    }

    public function test_cannot_process_already_approved_or_rejected_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hrd = User::factory()->create(['role' => 'hrd']);

        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000004',
            'name' => 'Karyawan D',
            'email' => 'karyawan.d@example.com',
            'phone' => '6281234567893',
            'department' => 'Tabinaco',
            'position' => 'Staff',
            'type' => 'sick',
            'leave_date' => '2026-09-08',
            'status' => 'approved',
            'processed_by' => $hrd->id,
            'processed_at' => now(),
        ]);

        // Coba reject pengajuan yang sudah approved
        $response = $this->actingAs($admin)->post("/pengajuan/{$leave->id}/reject", [
            'rejection_reason' => 'Mau diubah jadi tolak',
        ]);

        $response->assertSessionHas('error');
        $leave->refresh();
        $this->assertEquals('approved', $leave->status); // Tetap approved
    }

    public function test_whatsapp_service_generates_correct_prefilled_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Super Admin']);
        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000005',
            'name' => 'Karyawan E',
            'email' => 'karyawan.e@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Developer',
            'type' => 'leave',
            'leave_date' => '2026-09-22',
            'duration' => 3,
            'status' => 'approved',
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $waService = app(WhatsAppMessageService::class);
        $message = $waService->buildApprovedMessage($leave);
        $url = $waService->buildUrl($leave->phone, $message);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
        $this->assertStringContainsString('IZN-2026-000005', $message);
        $this->assertStringContainsString('DISETUJUI', $message);
        $this->assertStringContainsString('ADMIN', $message);
    }
}
