<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    /**
     * Send new leave request notification to the configured HRD/Admin Telegram group.
     *
     * @return array{success: bool, message: string}
     */
    public function sendNewRequestNotification(LeaveRequest $request): array
    {
        $botToken = config('telegram.bot_token');
        $chatId = config('telegram.chat_id');

        if (empty($botToken) || empty($chatId)) {
            $msg = 'Telegram bot token atau chat ID belum dikonfigurasi.';
            $request->update([
                'telegram_status' => 'failed',
                'telegram_error' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        }

        try {
            $leaveDateFormatted = Carbon::parse($request->leave_date)->format('d/m/Y');

            $message = "🔔 <b>PENGAJUAN IZIN BARU</b>\n\n"
                .'No. Pengajuan: <b>'.htmlspecialchars($request->request_number, ENT_QUOTES, 'UTF-8')."</b>\n"
                .'Nama: '.htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8')."\n"
                .'Departemen: '.htmlspecialchars($request->department, ENT_QUOTES, 'UTF-8')."\n"
                .'Jabatan: '.htmlspecialchars($request->position, ENT_QUOTES, 'UTF-8')."\n"
                .'Jenis Izin: '.htmlspecialchars($request->type_label, ENT_QUOTES, 'UTF-8')."\n"
                ."Tanggal Izin: {$leaveDateFormatted}\n"
                ."Status: 🟡 MENUNGGU PERSETUJUAN\n\n"
                .'Silakan buka dashboard untuk melihat detail dan memproses pengajuan.'."\n"
                .'Login Web: '.htmlspecialchars(config('telegram.login_url'), ENT_QUOTES, 'UTF-8');

            $loginUrl = config('telegram.login_url');

            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if (str_starts_with($loginUrl, 'https://')) {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Login Web Izin',
                                'url' => $loginUrl,
                            ],
                        ],
                    ],
                ];

                $payload['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_SLASHES);
            }

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

            if ($response->successful() && ($response->json('ok') === true)) {
                $request->update([
                    'telegram_status' => 'sent',
                    'telegram_sent_at' => Carbon::now('Asia/Jakarta'),
                    'telegram_error' => null,
                ]);

                return ['success' => true, 'message' => 'Notifikasi Telegram berhasil dikirim.'];
            }

            $errorMsg = 'Telegram API Error: '.($response->json('description') ?: $response->body());
            Log::warning("Gagal mengirim notifikasi Telegram pengajuan {$request->request_number}: {$errorMsg}");

            $request->update([
                'telegram_status' => 'failed',
                'telegram_error' => substr($errorMsg, 0, 500),
            ]);

            return ['success' => false, 'message' => $errorMsg];
        } catch (\Throwable $e) {
            Log::error("Exception saat mengirim notifikasi Telegram pengajuan {$request->request_number}: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            $request->update([
                'telegram_status' => 'failed',
                'telegram_error' => substr($e->getMessage(), 0, 500),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
