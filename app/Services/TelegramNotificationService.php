<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function __construct(protected WhatsAppMessageService $waService) {}

    /**
     * Send new leave request notification to the configured HRD/Admin Telegram group.
     *
     * @return array{success: bool, message: string}
     */
    public function sendNewRequestNotification(LeaveRequest $request): array
    {
        $message = "🔔 <b>PENGAJUAN IZIN BARU</b>\n\n"
            .$this->formatRequestSummary($request)
            ."Status: 🟡 MENUNGGU PERSETUJUAN HRD\n\n"
            .'HRD meninjau terlebih dahulu. Setelah disetujui HRD, Manager memberikan keputusan final.'."\n"
            .'Login Web: '.htmlspecialchars(config('telegram.login_url'), ENT_QUOTES, 'UTF-8');

        $result = $this->sendMessage($message, 'Login Web Izin', config('telegram.login_url'));

        if ($result['success']) {
            $request->update([
                'telegram_status' => 'sent',
                'telegram_sent_at' => Carbon::now('Asia/Jakarta'),
                'telegram_error' => null,
            ]);
        } else {
            $request->update([
                'telegram_status' => 'failed',
                'telegram_error' => substr($result['message'], 0, 500),
            ]);
        }

        return $result;
    }

    /**
     * Send HRD-approval notification (stage 1 of 2) to the configured HRD/Admin
     * Telegram group, informing the Manager that the request now awaits their
     * final decision.
     *
     * @return array{success: bool, message: string}
     */
    public function sendHrdApprovedNotification(LeaveRequest $request): array
    {
        $hrdName = $request->hrdProcessor->name ?? '-';

        $message = "🟢 <b>DISETUJUI HRD</b> — MENUNGGU MANAGER\n\n"
            .$this->formatRequestSummary($request)
            .'Disetujui HRD: '.htmlspecialchars($hrdName, ENT_QUOTES, 'UTF-8')."\n"
            ."Status: 🟡 MENUNGGU PERSETUJUAN MANAGER\n\n"
            .'Mohon Manager untuk meninjau dan memberikan keputusan final.'."\n"
            .'Login Web: '.htmlspecialchars(config('telegram.login_url'), ENT_QUOTES, 'UTF-8');

        return $this->sendMessage($message, 'Login Web Izin', config('telegram.login_url'));
    }

    /**
     * Send final-approval notification (approved by both HRD and Manager) to the
     * configured HRD/Admin Telegram group, with a button that forwards the
     * approval confirmation straight to the employee's WhatsApp.
     *
     * @return array{success: bool, message: string}
     */
    public function sendApprovedNotification(LeaveRequest $request): array
    {
        $hrdName = $request->hrdProcessor->name ?? '-';
        $managerName = $request->processor->name ?? '-';

        $message = "✅ <b>PENGAJUAN IZIN DISETUJUI</b> (FINAL)\n\n"
            .$this->formatRequestSummary($request)
            .'Disetujui HRD: '.htmlspecialchars($hrdName, ENT_QUOTES, 'UTF-8')."\n"
            .'Disetujui Manager: '.htmlspecialchars($managerName, ENT_QUOTES, 'UTF-8')."\n"
            ."Status: ✅ DISETUJUI\n\n"
            .'Silakan teruskan konfirmasi ke karyawan lewat tombol WhatsApp di bawah ini.';

        $waMessage = $this->waService->buildApprovedMessage($request);
        $waUrl = $this->waService->buildUrl($request->phone, $waMessage);

        return $this->sendMessage($message, $waUrl !== '' ? 'Kirim ke WhatsApp Karyawan' : null, $waUrl);
    }

    /**
     * Build the shared "request summary" block used by every notification variant.
     */
    private function formatRequestSummary(LeaveRequest $request): string
    {
        $leaveDateFormatted = Carbon::parse($request->leave_date)->format('d/m/Y');

        return 'No. Pengajuan: <b>'.htmlspecialchars($request->request_number, ENT_QUOTES, 'UTF-8')."</b>\n"
            .'Nama: '.htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8')."\n"
            .'Departemen: '.htmlspecialchars($request->department, ENT_QUOTES, 'UTF-8')."\n"
            .'Jabatan: '.htmlspecialchars($request->position, ENT_QUOTES, 'UTF-8')."\n"
            .'Jenis Izin: '.htmlspecialchars($request->type_label, ENT_QUOTES, 'UTF-8')."\n"
            ."Tanggal Izin: {$leaveDateFormatted}\n";
    }

    /**
     * Post a message to the configured Telegram group, optionally with a single
     * inline button. The button is only attached when its URL is https:// —
     * Telegram rejects http:// button URLs outright.
     *
     * @return array{success: bool, message: string}
     */
    private function sendMessage(string $text, ?string $buttonText, ?string $buttonUrl): array
    {
        $botToken = config('telegram.bot_token');
        $chatId = config('telegram.chat_id');

        if (empty($botToken) || empty($chatId)) {
            return ['success' => false, 'message' => 'Telegram bot token atau chat ID belum dikonfigurasi.'];
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($buttonText !== null && $buttonUrl !== null && str_starts_with($buttonUrl, 'https://')) {
                $payload['reply_markup'] = json_encode([
                    'inline_keyboard' => [[['text' => $buttonText, 'url' => $buttonUrl]]],
                ], JSON_UNESCAPED_SLASHES);
            }

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

            if ($response->successful() && ($response->json('ok') === true)) {
                return ['success' => true, 'message' => 'Notifikasi Telegram berhasil dikirim.'];
            }

            $errorMsg = 'Telegram API Error: '.($response->json('description') ?: $response->body());
            Log::warning("Gagal mengirim notifikasi Telegram: {$errorMsg}");

            return ['success' => false, 'message' => $errorMsg];
        } catch (\Throwable $e) {
            Log::error('Exception saat mengirim notifikasi Telegram: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
