<?php

namespace Tests\Feature;

use App\Mail\LeaveRequestApprovedMail;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PartialDayLeaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_types_are_top_level_options_without_the_half_day_dropdown(): void
    {
        $this->get(route('public.form'))->assertOk()
            ->assertSee('value="early_departure"', false)
            ->assertSee('value="temporary_exit"', false)
            ->assertSee('Jam Rencana Pulang')
            ->assertSee('Estimasi Jam Kembali')
            ->assertDontSee('name="half_day_type"', false);
    }

    public function test_early_departure_accepts_same_day_and_future_dates_without_duration_limits(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setTime(10, 0));
        foreach ([today('Asia/Jakarta'), today('Asia/Jakarta')->addDay()] as $date) {
            $this->post(route('public.store'), $this->payload('early_departure', [
                'leave_date' => $date->toDateString(),
                'start_time' => '11:00',
                'end_time' => '20:00',
                'duration' => 9,
                'half_day_type' => 'Pulang lebih awal',
            ]))->assertSessionHasNoErrors()->assertRedirect();
        }

        $this->assertDatabaseCount('leave_requests', 2);
        foreach (LeaveRequest::all() as $leave) {
            $this->assertSame('early_departure', $leave->type);
            $this->assertSame('11:00', substr($leave->start_time, 0, 5));
            $this->assertNull($leave->end_time);
            $this->assertNull($leave->duration);
            $this->assertNull($leave->half_day_type);
            $this->assertSame('pending', $leave->status);
        }
        Http::assertSentCount(2);
    }

    public function test_temporary_exit_accepts_more_than_four_hours_and_calculates_duration(): void
    {
        $this->post(route('public.store'), $this->payload('temporary_exit', [
            'start_time' => '10:00',
            'end_time' => '15:30',
            'duration' => 1,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $leave = LeaveRequest::sole();
        $this->assertSame('temporary_exit', $leave->type);
        $this->assertSame(5.5, $leave->duration);
        $this->assertSame('pending', $leave->status);
        Http::assertSentCount(1);
    }

    public function test_new_types_require_date_time_and_reason_and_reject_past_dates(): void
    {
        foreach (['early_departure', 'temporary_exit'] as $type) {
            foreach ([
                ['leave_date' => today('Asia/Jakarta')->subDay()->toDateString()],
                ['leave_date' => 'invalid'],
                ['start_time' => null],
                ['start_time' => '25:90'],
                ['reason' => ''],
            ] as $invalid) {
                $this->post(route('public.store'), $this->payload($type, $invalid))
                    ->assertSessionHasErrors(array_keys($invalid));
            }
        }
        $this->assertDatabaseCount('leave_requests', 0);
        Http::assertNothingSent();
    }

    public function test_temporary_exit_requires_a_valid_return_time_after_departure(): void
    {
        foreach ([null, 'invalid', '09:00', '10:00'] as $returnTime) {
            $this->post(route('public.store'), $this->payload('temporary_exit', [
                'start_time' => '10:00',
                'end_time' => $returnTime,
            ]))->assertSessionHasErrors('end_time');
        }
        $this->assertDatabaseCount('leave_requests', 0);
        Http::assertNothingSent();
    }

    public function test_half_day_no_longer_requires_a_subtype(): void
    {
        $this->post(route('public.store'), $this->payload('half_day', [
            'leave_date' => today('Asia/Jakarta')->addDay()->toDateString(),
            'start_time' => '08:30',
            'end_time' => '12:30',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull(LeaveRequest::sole()->half_day_type);
        $this->assertSame(4.0, LeaveRequest::sole()->duration);
        Http::assertSentCount(1);
    }

    public function test_new_types_keep_two_stage_approval_and_show_correct_schedule_in_outputs(): void
    {
        Mail::fake();
        $hrd = User::factory()->create(['role' => 'hrd']);
        $manager = User::factory()->create(['role' => 'admin']);
        $early = LeaveRequest::factory()->create([
            'type' => 'early_departure', 'start_time' => '14:00', 'duration' => null,
        ]);
        $exit = LeaveRequest::factory()->create([
            'type' => 'temporary_exit', 'start_time' => '10:00', 'end_time' => '15:30', 'duration' => 5.5,
        ]);

        foreach ([$early, $exit] as $leave) {
            $this->actingAs($manager)->post(route('requests.approve', $leave))->assertForbidden();
            $this->actingAs($hrd)->post(route('requests.approve', $leave))->assertSessionHas('success');
            $this->assertSame('pending_manager', $leave->fresh()->status);
            $this->actingAs($manager)->post(route('requests.approve', $leave))->assertSessionHas('success');
            $this->assertSame('approved', $leave->fresh()->status);
            $this->get(route('requests.show', $leave))->assertOk()->assertSee($leave->type_label);
        }
        Mail::assertSentCount(2);

        $this->get(route('requests.show', $early))->assertSee('Jam Rencana Pulang')->assertSee('14:00');
        $this->get(route('requests.show', $exit))->assertSee('5.5 Jam');
        foreach (['requests.index', 'requests.history', 'admin.dashboard'] as $route) {
            $this->get(route($route, ['type' => 'early_departure']))->assertOk()
                ->assertSee($early->request_number)->assertDontSee($exit->request_number);
        }
        $this->get(route('reports.index'))->assertOk()->assertViewHas('byType', fn (array $types): bool => $types['early_departure'] === 1 && $types['temporary_exit'] === 1);
        $csv = $this->get(route('reports.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Pulang pukul 14:00', $csv);
        $this->assertStringContainsString('5.5 Jam', $csv);
        $this->assertStringContainsString('Jam Rencana Pulang', (new LeaveRequestApprovedMail($early->fresh()))->render());
        $this->assertStringContainsString('5.5 Jam', (new LeaveRequestApprovedMail($exit->fresh()))->render());
        $service = app(WhatsAppMessageService::class);
        $this->assertStringContainsString('Pulang: 14:00', $service->buildApprovedMessage($early->fresh()));
        $this->assertStringContainsString('15:30', $service->buildApprovedMessage($exit->fresh()));

        auth()->logout();
        $this->post(route('status.check'), ['request_number' => $early->request_number, 'email' => $early->email])
            ->assertOk()->assertSee('Jam Rencana Pulang')->assertSee('14:00');
        $this->post(route('status.check'), ['request_number' => $exit->request_number, 'email' => $exit->email])
            ->assertOk()->assertSee('5.5 Jam');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(string $type, array $overrides = []): array
    {
        return array_replace([
            'name' => 'Karyawan Pengaju',
            'email' => 'karyawan@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Sales',
            'type' => $type,
            'leave_date' => today('Asia/Jakarta')->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'reason' => 'Keperluan keluarga.',
        ], $overrides);
    }
}
