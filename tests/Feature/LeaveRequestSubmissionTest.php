<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'position' => 'Technical Support',
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
            'position' => 'Technical Support',
            'type' => 'late',
            'status' => 'pending',
            'estimated_arrival' => '09:00',
        ]);

        $leave = LeaveRequest::first();
        $this->assertMatchesRegularExpression('/^IZN-2026-\d{6}$/', $leave->request_number);
        $response->assertRedirect(route('public.success', $leave->request_number));
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
            'position' => 'Staff',
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
            'position' => 'Staff',
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

    public function test_late_request_arrival_after_09_30_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Staff',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:45', // Melebihi 09.30
            'reason' => 'Urusan keluarga',
            'agreement' => '1',
        ]);

        $response->assertSessionHasErrors('estimated_arrival');
    }

    public function test_half_day_request_less_than_h_minus_1_fails(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 10, 0, 0, 'Asia/Jakarta'));

        // Hari ini (bukan minimal H-1) -> gagal
        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Diana',
            'email' => 'diana@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Designer',
            'type' => 'half_day',
            'leave_date' => '2026-09-08',
            'half_day_type' => 'Pulang lebih awal',
            'start_time' => '13:00',
            'end_time' => '17:00',
            'reason' => 'Acara keluarga',
        ]);

        $response->assertSessionHasErrors('leave_date');

        // Besok (H-1) -> berhasil
        $responseValid = $this->post('/ajukan-izin', [
            'name' => 'Diana',
            'email' => 'diana@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Designer',
            'type' => 'half_day',
            'leave_date' => '2026-09-09',
            'half_day_type' => 'Pulang lebih awal',
            'start_time' => '13:00',
            'end_time' => '17:00',
            'reason' => 'Acara keluarga',
        ]);

        $responseValid->assertSessionHasNoErrors();
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

    public function test_personal_leave_on_same_day_requires_emergency(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 10, 0, 0, 'Asia/Jakarta'));

        // Hari H tanpa emergency -> gagal
        $response = $this->from('/ajukan-izin')->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Accountant',
            'type' => 'personal',
            'leave_date' => '2026-09-08',
            'reason' => 'Urusan mendadak',
        ]);

        $response->assertSessionHasErrors('emergency');

        // Hari H dengan emergency dan alasan -> berhasil
        $responseSuccess = $this->post('/ajukan-izin', [
            'name' => 'Fani',
            'email' => 'fani@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Accountant',
            'type' => 'personal',
            'leave_date' => '2026-09-08',
            'emergency' => '1',
            'emergency_reason' => 'Rumah kebanjiran mendadak tadi pagi',
            'reason' => 'Membersihkan dan mengamankan perabot rumah',
        ]);

        $responseSuccess->assertSessionHasNoErrors();
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
            'position' => 'Engineer',
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
            'position' => 'Engineer',
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
            'position' => 'Engineer',
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
