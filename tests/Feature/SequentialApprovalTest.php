<?php

namespace Tests\Feature;

use App\Mail\LeaveRequestApprovedMail;
use App\Mail\LeaveRequestRejectedMail;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SequentialApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_approval_preserves_both_reviews_and_sends_one_final_email(): void
    {
        Mail::fake();
        $this->travelTo(now()->startOfSecond());
        $hrd = User::factory()->create(['role' => 'hrd']);
        $manager = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::factory()->create();

        $this->actingAs($hrd)->post(route('requests.approve', $leave->id), [
            'approval_note' => 'HRD sudah memeriksa jadwal.',
        ])->assertSessionHas('success');
        $hrdReviewedAt = now();
        Mail::assertNothingSent();

        $this->travel(1)->hours();
        $this->actingAs($manager)->post(route('requests.approve', $leave->id), [
            'approval_note' => 'Manager menyetujui izin.',
        ])->assertSessionHas('success');

        $leave->refresh();
        $this->assertSame('approved', $leave->status);
        $this->assertSame($hrd->id, $leave->hrd_processed_by);
        $this->assertSame('approved', $leave->hrd_decision);
        $this->assertTrue($leave->hrd_processed_at->equalTo($hrdReviewedAt));
        $this->assertSame('HRD sudah memeriksa jadwal.', $leave->hrd_note);
        $this->assertSame($manager->id, $leave->processed_by);
        $this->assertTrue($leave->processed_at->equalTo(now()));
        $this->assertSame('Manager menyetujui izin.', $leave->approval_note);
        $this->assertSame('sent', $leave->email_status);
        Mail::assertSent(LeaveRequestApprovedMail::class, fn ($mail): bool => $mail->hasTo($leave->email));
        Mail::assertSentCount(1);

        foreach ([$hrd, $manager] as $reviewer) {
            foreach (['approve', 'reject'] as $action) {
                $this->actingAs($reviewer)->post(route('requests.'.$action, $leave->id), [
                    'rejection_reason' => 'Percobaan mengubah keputusan final.',
                ])->assertSessionHas('error');
            }
        }
        $this->assertSame('approved', $leave->fresh()->status);
        Mail::assertSentCount(1);
    }

    public function test_hrd_rejection_stops_the_workflow_for_both_reviewers(): void
    {
        Mail::fake();
        $hrd = User::factory()->create(['role' => 'hrd']);
        $manager = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::factory()->create();

        $this->actingAs($hrd)->post(route('requests.reject', $leave->id), [
            'rejection_reason' => 'Jadwal tidak memungkinkan.',
        ])->assertSessionHas('success');

        foreach ([$hrd, $manager] as $reviewer) {
            foreach (['approve', 'reject'] as $action) {
                $this->actingAs($reviewer)->post(route('requests.'.$action, $leave->id), [
                    'rejection_reason' => 'Percobaan mengubah keputusan.',
                ])->assertSessionHas('error');
            }
        }

        $leave->refresh();
        $this->assertSame('rejected', $leave->status);
        $this->assertSame('rejected', $leave->hrd_decision);
        $this->assertSame($hrd->id, $leave->hrd_processed_by);
        $this->assertSame($hrd->id, $leave->processed_by);
        $this->assertSame('Jadwal tidak memungkinkan.', $leave->rejection_reason);
        Mail::assertSent(LeaveRequestRejectedMail::class);
        Mail::assertSentCount(1);
    }

    public function test_manager_can_reject_after_hrd_approval_with_a_required_reason(): void
    {
        Mail::fake();
        $hrd = User::factory()->create(['role' => 'hrd']);
        $manager = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::factory()->create();
        $this->actingAs($hrd)->post(route('requests.approve', $leave->id), [
            'approval_note' => 'Lolos pemeriksaan HRD.',
        ])->assertSessionHas('success');

        $this->actingAs($manager)->post(route('requests.reject', $leave->id), [
            'rejection_reason' => ' ',
        ])->assertSessionHasErrors('rejection_reason');
        $this->assertSame('pending_manager', $leave->fresh()->status);
        Mail::assertNothingSent();

        $this->post(route('requests.reject', $leave->id), [
            'rejection_reason' => 'Tim membutuhkan kehadiran karyawan.',
        ])->assertSessionHas('success');

        $leave->refresh();
        $this->assertSame('rejected', $leave->status);
        $this->assertSame('approved', $leave->hrd_decision);
        $this->assertSame('Lolos pemeriksaan HRD.', $leave->hrd_note);
        $this->assertSame($manager->id, $leave->processed_by);
        $this->assertSame('Tim membutuhkan kehadiran karyawan.', $leave->rejection_reason);
        Mail::assertSent(LeaveRequestRejectedMail::class);
        Mail::assertSentCount(1);
    }

    public function test_reviewers_cannot_skip_or_repeat_stages_and_forms_follow_permissions(): void
    {
        Mail::fake();
        $hrd = User::factory()->create(['role' => 'hrd']);
        $manager = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::factory()->create();
        $approvalUrl = route('requests.approve', $leave->id);
        $rejectionUrl = route('requests.reject', $leave->id);

        $this->actingAs($manager)->get(route('requests.show', $leave->id))
            ->assertOk()->assertSee('Menunggu HRD')->assertDontSee($approvalUrl, false)->assertDontSee($rejectionUrl, false);
        $this->post($rejectionUrl, ['rejection_reason' => 'Melewati HRD.'])->assertForbidden();
        $this->assertSame('pending', $leave->fresh()->status);

        $this->actingAs($hrd)->get(route('requests.show', $leave->id))
            ->assertOk()->assertSee($approvalUrl, false)->assertSee($rejectionUrl, false);
        $this->post($approvalUrl)->assertSessionHas('success');
        foreach (['approve', 'reject'] as $action) {
            $this->post(route('requests.'.$action, $leave->id), [
                'rejection_reason' => 'Mengulang peninjauan HRD.',
            ])->assertForbidden();
        }
        $this->get(route('requests.show', $leave->id))->assertOk()
            ->assertSee('Menunggu Manager')->assertDontSee($approvalUrl, false)->assertDontSee($rejectionUrl, false)
            ->assertDontSee('Kirim via WhatsApp');
        $this->actingAs($manager)->get(route('requests.show', $leave->id))
            ->assertOk()->assertSee($approvalUrl, false)->assertSee($rejectionUrl, false);
        $this->assertSame('pending_manager', $leave->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_approval_notes_are_validated_before_recording_a_decision(): void
    {
        Mail::fake();
        $hrd = User::factory()->create(['role' => 'hrd']);
        $leave = LeaveRequest::factory()->create();
        foreach ([['invalid'], str_repeat('a', 1001)] as $note) {
            $this->actingAs($hrd)->post(route('requests.approve', $leave->id), [
                'approval_note' => $note,
            ])->assertSessionHasErrors('approval_note');
        }
        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertNull($leave->fresh()->hrd_processed_at);
        Mail::assertNothingSent();
    }

    public function test_pending_manager_is_visible_in_status_filters_dashboards_and_reports(): void
    {
        Mail::fake();
        $hrd = User::factory()->create(['role' => 'hrd']);
        $manager = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::factory()->create();
        $pending = LeaveRequest::factory()->create();
        $this->actingAs($hrd)->post(route('requests.approve', $leave->id), [
            'approval_note' => 'Catatan internal HRD.',
        ])->assertSessionHas('success');

        $this->get(route('hrd.dashboard'))->assertOk()->assertViewHas('stats', fn (array $stats): bool => $stats['pending'] === 1);
        $this->actingAs($manager)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['pending'] === 1);
        foreach (['requests.index', 'requests.history', 'admin.dashboard'] as $route) {
            $this->get(route($route, ['status' => 'pending_manager']))->assertOk()
                ->assertSee($leave->request_number)->assertDontSee($pending->request_number);
        }
        $this->get(route('reports.index'))->assertOk()
            ->assertViewHas('byStatus', fn (array $counts): bool => $counts === [
                'pending' => 1, 'pending_manager' => 1, 'approved' => 0, 'rejected' => 0,
            ]);
        $csv = $this->get(route('reports.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Menunggu Manager', $csv);
        $this->assertStringContainsString('Disetujui HRD', $csv);
        $this->assertStringContainsString('Catatan internal HRD.', $csv);

        auth()->logout();
        $this->post(route('status.check'), [
            'request_number' => $leave->request_number,
            'email' => $leave->email,
        ])->assertOk()->assertSee('Menunggu Manager')->assertDontSee('Catatan internal HRD.');
        Mail::assertNothingSent();
    }

    public function test_guests_and_other_roles_cannot_record_review_decisions(): void
    {
        Mail::fake();
        $leave = LeaveRequest::factory()->create();
        foreach (['approve', 'reject'] as $action) {
            $this->post(route('requests.'.$action, $leave->id), [
                'rejection_reason' => 'Tanpa akses.',
            ])->assertRedirect(route('login'));
        }

        $otherUser = User::factory()->create(['role' => 'employee']);
        foreach (['approve', 'reject'] as $action) {
            $this->actingAs($otherUser)->post(route('requests.'.$action, $leave->id), [
                'rejection_reason' => 'Tanpa akses.',
            ])->assertForbidden();
        }
        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertNull($leave->fresh()->hrd_decision);
        Mail::assertNothingSent();
    }

    public function test_manager_cannot_finalize_a_request_without_hrd_approval_evidence(): void
    {
        Mail::fake();
        $manager = User::factory()->create(['role' => 'admin']);
        $leave = LeaveRequest::factory()->create(['status' => 'pending_manager']);
        foreach (['approve', 'reject'] as $action) {
            $this->actingAs($manager)->post(route('requests.'.$action, $leave->id), [
                'rejection_reason' => 'Tidak ada keputusan HRD.',
            ])->assertForbidden();
        }
        $this->assertSame('pending_manager', $leave->fresh()->status);
        $this->assertNull($leave->fresh()->processed_by);
        Mail::assertNothingSent();
    }
}
