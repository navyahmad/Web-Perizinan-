<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_request_submission_triggers_telegram_service(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        Config::set('telegram.bot_token', 'mock_token_123');
        Config::set('telegram.chat_id', '-1001234567890');
        Config::set('app.url', 'https://izin.generalsolusindo.com');

        $this->fakeTelegramResponse(Http::response(['ok' => true, 'result' => ['message_id' => 999]], 200));

        $response = $this->post('/ajukan-izin', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Macet',
            'agreement' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        $leave = LeaveRequest::first();
        $this->assertNotNull($leave);
        $this->assertEquals('sent', $leave->telegram_status);
        $this->assertNotNull($leave->telegram_sent_at);
        $this->assertNull($leave->telegram_error);

        $recorded = Http::recorded();
        $this->assertNotEmpty($recorded);

        $sentRequest = $recorded[0][0];
        $sentData = $sentRequest->data();

        $this->assertStringContainsString('mock_token_123/sendMessage', $sentRequest->url());
        $this->assertEquals('-1001234567890', $sentData['chat_id']);
        $this->assertStringContainsString($leave->request_number, $sentData['text']);
        $this->assertStringContainsString('Budi Santoso', $sentData['text']);
        $this->assertStringContainsString('Teknisi', $sentData['text']);
        $this->assertStringContainsString('Izin Terlambat', $sentData['text']);
        $this->assertStringContainsString("https://izin.generalsolusindo.com/pengajuan/{$leave->id}", $sentData['reply_markup']);
    }

    public function test_telegram_failure_does_not_rollback_leave_request(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        Config::set('telegram.bot_token', 'mock_token_123');
        Config::set('telegram.chat_id', '-1001234567890');

        // Simulate Telegram API 500 error
        $this->fakeTelegramResponse(Http::response(['ok' => false, 'description' => 'Unauthorized / Bad Request'], 400));

        $response = $this->post('/ajukan-izin', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'phone' => '081234567890',
            'department' => 'Tabinaco',
            'position' => 'Finance',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Kendala pagi',
            'agreement' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        // Database record must still exist and remain pending
        $leave = LeaveRequest::where('email', 'siti@example.com')->first();
        $this->assertNotNull($leave);
        $this->assertEquals('pending', $leave->status);
        $this->assertEquals('failed', $leave->telegram_status);
        $this->assertNull($leave->telegram_sent_at);
        $this->assertNotNull($leave->telegram_error);
        $this->assertStringContainsString('Unauthorized / Bad Request', $leave->telegram_error);
    }

    public function test_telegram_exception_is_handled_safely(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        Config::set('telegram.bot_token', 'mock_token_123');
        Config::set('telegram.chat_id', '-1001234567890');

        // Simulate network timeout or exception
        $this->fakeTelegramResponse(function () {
            throw new ConnectionException('Connection timeout');
        });

        $response = $this->post('/ajukan-izin', [
            'name' => 'Andi Wijaya',
            'email' => 'andi@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'Operasional',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Kendala jalan',
            'agreement' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        $leave = LeaveRequest::where('email', 'andi@example.com')->first();
        $this->assertNotNull($leave);
        $this->assertEquals('pending', $leave->status);
        $this->assertEquals('failed', $leave->telegram_status);
        $this->assertStringContainsString('Connection timeout', $leave->telegram_error);
    }

    public function test_refreshing_success_page_does_not_resend_telegram_notification(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 8, 6, 30, 0, 'Asia/Jakarta'));

        Config::set('telegram.bot_token', 'mock_token_123');
        Config::set('telegram.chat_id', '-1001234567890');

        $this->fakeTelegramResponse(Http::response(['ok' => true, 'result' => []], 200));

        $this->post('/ajukan-izin', [
            'name' => 'Rina',
            'email' => 'rina@example.com',
            'phone' => '081234567890',
            'department' => 'General Solusindo',
            'position' => 'HR',
            'type' => 'late',
            'leave_date' => '2026-09-08',
            'estimated_arrival' => '09:00',
            'reason' => 'Kendala jalan',
            'agreement' => '1',
        ]);

        Http::assertSentCount(1);

        $leave = LeaveRequest::where('email', 'rina@example.com')->first();

        // Refresh success page via GET multiple times
        $this->get(route('public.success', $leave->request_number))->assertStatus(200);
        $this->get(route('public.success', $leave->request_number))->assertStatus(200);

        // Telegram HTTP call must still be 1 (never resent on GET)
        Http::assertSentCount(1);
    }

    public function test_telegram_service_does_not_leak_private_file_paths_or_passwords(): void
    {
        Config::set('telegram.bot_token', 'mock_token_123');
        Config::set('telegram.chat_id', '-1001234567890');

        $this->fakeTelegramResponse(Http::response(['ok' => true, 'result' => []], 200));

        $leave = LeaveRequest::create([
            'request_number' => 'IZN-2026-000099',
            'name' => 'Doni <script>',
            'email' => 'doni@example.com',
            'phone' => '6281234567890',
            'department' => 'General Solusindo',
            'position' => 'Teknisi',
            'type' => 'emergency',
            'leave_date' => '2026-09-08',
            'reason' => 'Darurat pribadi',
            'status' => 'pending',
        ]);

        $service = new TelegramNotificationService;
        $service->sendNewRequestNotification($leave);

        Http::assertSent(function ($request) {
            $text = $request->data()['text'];

            // HTML entities sanitized
            return str_contains($text, 'Doni &lt;script&gt;')
                && ! str_contains($text, 'attachments/')
                && ! str_contains($text, 'password')
                && ! str_contains($text, 'storage/app');
        });
    }
}
