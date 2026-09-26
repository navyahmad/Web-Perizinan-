<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_employee_can_submit_valid_leave_request_without_account(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $response = $this->post('/ajukan-izin', [
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Ban motor bocor di perjalanan.',
            'agreement' => '1',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@example.com',
            'phone' => '6281234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'late',
            'status' => 'pending',
            'estimated_arrival' => '09:00',
        ]);

        $leave = LeaveRequest::first();
        $this->assertMatchesRegularExpression('/^IZN-2026-\d{6}$/', $leave->request_number);
        $this->assertSame('sent', $leave->telegram_status);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottesting_telegram_bot_token/sendMessage');
        $response->assertRedirect(route('public.success', $leave->request_number));
    }

    public function test_request_number_generator_allocates_unique_sequential_numbers(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $service = app(LeaveRequestService::class);

        $firstNumber = $service->generateRequestNumber();
        $secondNumber = $service->generateRequestNumber();

        $this->assertSame('IZN-2026-000001', $firstNumber);
        $this->assertSame('IZN-2026-000002', $secondNumber);
        $this->assertDatabaseHas('leave_request_sequences', [
            'year' => 2026,
            'last_sequence' => 2,
        ]);
    }

    public function test_late_request_after_07_00_wib_requires_emergency(): void
    {
        // 07:15 WIB (Lewat pukul 07.00 WIB)
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 7, 15, 0, 'Asia/Jakarta'));

        // Tanpa emergency -> gagal
        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Operasional',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Macet',
            'agreement' => '1',
        ]);

        $response->assertSessionHasErrors('emergency');

        // Dengan emergency & emergency_reason -> berhasil
        $responseSuccess = $this->post('/ajukan-izin', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Operasional',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Macet',
            'emergency' => '1',
            'emergency_reason' => 'Ada kecelakaan lalu lintas mendadak di jalan tol.',
            'agreement' => '1',
        ]);

        $responseSuccess->assertSessionHasNoErrors();
    }

    public function test_late_request_arrival_at_09_30_succeeds(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $response = $this->post('/ajukan-izin', [
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Operasional',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:30',
            'reason' => 'Urusan keluarga',
            'agreement' => '1',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_late_request_arrival_after_09_30_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Operasional',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:45', // Melebihi 09.30
            'reason' => 'Urusan keluarga',
            'agreement' => '1',
        ]);

        $response->assertSessionHasErrors('estimated_arrival');
    }

    public function test_half_day_request_in_the_past_fails_but_today_succeeds(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 10, 0, 0, 'Asia/Jakarta'));

        // Tanggal kemarin -> gagal
        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Diana',
            'email' => 'diana@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Marketing',
            'type' => 'half_day',
            'leave_date' => '2026-09-07',
            'start_time' => '15:00',
            'end_time' => '16:30',
            'reason' => 'Acara keluarga',
        ]);

        $response->assertSessionHasErrors('leave_date');

        // Hari ini (urgent) -> berhasil
        $responseValid = $this->post('/ajukan-izin', [
            'name' => 'Diana',
            'email' => 'diana@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Marketing',
            'type' => 'half_day',
            'leave_date' => '2026-09-08',
            'start_time' => '15:00',
            'end_time' => '16:30',
            'reason' => 'Acara keluarga',
        ]);

        $responseValid->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', [
            'name' => 'Diana',
            'duration' => 1.5,
        ]);
    }

    public function test_half_day_request_starting_before_12_30_fails(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 10, 0, 0, 'Asia/Jakarta'));

        // Jam Mulai 09:00 berarti baru bekerja 0.5 jam sejak 08.30 -> gagal
        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Diana',
            'email' => 'diana@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Marketing',
            'type' => 'half_day',
            'leave_date' => '2026-09-09',
            'start_time' => '09:00',
            'end_time' => '16:30',
            'reason' => 'Acara keluarga',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_half_day_request_starting_at_or_after_12_30_succeeds(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 10, 0, 0, 'Asia/Jakarta'));

        // Jam Mulai tepat 12:30 berarti sudah bekerja tepat 4 jam sejak 08.30 -> berhasil
        $response = $this->post('/ajukan-izin', [
            'name' => 'Diana',
            'email' => 'diana@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Marketing',
            'type' => 'half_day',
            'leave_date' => '2026-09-09',
            'start_time' => '12:30',
            'end_time' => '16:30',
            'reason' => 'Acara keluarga',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', [
            'name' => 'Diana',
            'duration' => 4.0,
        ]);
    }

    public function test_cuti_less_than_h_minus_7_fails(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 10, 0, 0, 'Asia/Jakarta'));

        // Hanya 3 hari ke depan -> gagal
        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Eko',
            'email' => 'eko@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Sales',
            'type' => 'leave',
            'leave_date' => '2026-09-11',
            'duration' => 2,
            'reason' => 'Liburan keluarga',
        ]);

        $response->assertSessionHasErrors('leave_date');

        // 7 hari ke depan (2026-09-15) -> berhasil
        $responseSuccess = $this->post('/ajukan-izin', [
            'name' => 'Eko',
            'email' => 'eko@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Sales',
            'type' => 'leave',
            'leave_date' => '2026-09-15',
            'duration' => 2,
            'reason' => 'Liburan keluarga',
        ]);

        $responseSuccess->assertSessionHasNoErrors();
    }

    public function test_emergency_leave_today_before_08_30_wib_succeeds(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 7, 45, 0, 'Asia/Jakarta'));

        $response = $this->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'emergency',
            'leave_date' => '2026-09-08',
            'reason' => 'Ada musibah keluarga mendadak pagi ini.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', [
            'name' => 'Fani',
            'type' => 'emergency',
            'emergency' => 1,
            'duration' => 1.0,
        ]);
    }

    public function test_emergency_leave_today_exactly_at_08_30_00_wib_succeeds(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 8, 30, 0, 'Asia/Jakarta'));

        $response = $this->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'emergency',
            'leave_date' => '2026-09-08',
            'reason' => 'Ada keperluan darurat.',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_emergency_leave_after_08_30_00_wib_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 8, 30, 1, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'emergency',
            'leave_date' => '2026-09-08',
            'reason' => 'Darurat',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_emergency_leave_for_tomorrow_fails(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 7, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'emergency',
            'leave_date' => '2026-09-09',
            'reason' => 'Darurat besok',
        ]);

        $response->assertSessionHasErrors('leave_date');
    }

    public function test_emergency_leave_for_yesterday_fails(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 7, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'emergency',
            'leave_date' => '2026-09-07',
            'reason' => 'Darurat kemarin',
        ]);

        $response->assertSessionHasErrors('leave_date');
    }

    public function test_emergency_leave_requires_reason(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 7, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'emergency',
            'leave_date' => '2026-09-08',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_personal_type_is_no_longer_accepted(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 7, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'personal',
            'leave_date' => '2026-09-08',
            'reason' => 'Izin pribadi lama',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_position_allowlist_accepts_all_12_options(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $positions = [
            'Sales', 'Marketing', 'Finance', 'Operasional',
            'Procurement', 'Teknisi', 'Internship', 'Admin Store',
            'Content Creator', 'HR', 'Digital Merketing', 'SI Officer',
        ];

        foreach ($positions as $index => $pos) {
            $response = $this->post('/ajukan-izin', [
                'name' => "Karyawan {$index}",
                'email' => "user{$index}@example.com",
                'phone' => '081234567890',
                'department' => 'General Solusindo',
                'position' => $pos,
                'type' => 'late',
                'leave_date' => '2026-09-08',
                'estimated_arrival' => '09:00',
                'reason' => 'Ada kendala perjalanan.',
                'agreement' => '1',
            ]);

            $response->assertSessionHasNoErrors();
        }
    }

    public function test_position_outside_allowlist_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'CEO / Random Position',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Kendala perjalanan',
            'agreement' => '1',
        ]);

        $response->assertSessionHasErrors('position');
    }

    public function test_sick_leave_rules_with_work_hours_and_doctor_note(): void
    {
        // 08:45 WIB (Setelah jam kerja dimulai: 08.30 WIB)
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 8, 45, 0, 'Asia/Jakarta'));

        // Lapor sakit hari H setelah 08.30 tanpa emergency -> gagal
        $responseNoEmergency = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Gilang',
            'email' => 'gilang@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'sick',
            'leave_date' => '2026-09-08',
            'duration' => 1,
            'reason' => 'Demam tinggi',
            'contactable' => '1',
        ]);

        $responseNoEmergency->assertSessionHasErrors('emergency');

        // Lapor sakit > 1 hari tanpa surat dokter -> gagal
        $responseNoDoc = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Gilang',
            'email' => 'gilang@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'sick',
            'leave_date' => '2026-09-08',
            'duration' => 3, // > 1 hari
            'emergency' => '1',
            'emergency_reason' => 'Tadi pingsan dan baru sadar di klinik',
            'reason' => 'Tifus',
            'contactable' => '0',
        ]);

        $responseNoDoc->assertSessionHasErrors('attachments');

        // Lapor sakit > 1 hari dengan file surat dokter -> berhasil
        $fakeDoctorNote = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $responseWithDoc = $this->post('/ajukan-izin', [
            'name' => 'Gilang',
            'email' => 'gilang@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'sick',
            'leave_date' => '2026-09-08',
            'duration' => 3,
            'emergency' => '1',
            'emergency_reason' => 'Tadi pingsan dan baru sadar di klinik',
            'reason' => 'Tifus',
            'contactable' => '0',
            'attachments' => [$fakeDoctorNote],
        ]);

        $responseWithDoc->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', [
            'name' => 'Gilang',
            'duration' => 3.0,
            'type' => 'sick',
        ]);
        $this->assertDatabaseHas('leave_request_attachments', [
            'file_name' => 'surat_dokter.pdf',
        ]);
    }
}
