<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identitas Karyawan (Snapshot)
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:25'],
            'department' => ['required', 'in:General Solusindo,Tabinaco'],
            'position' => [
                'required',
                'in:Sales,Marketing,Finance,Operasional,Procurement,Teknisi,Internship,Admin Store,Content Creator,HR,Digital Merketing,SI Officer',
            ],
            'type' => ['required', 'in:late,half_day,leave,emergency,sick'],
            'leave_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'emergency' => ['nullable', 'boolean'],
            'emergency_reason' => ['nullable', 'string', 'max:1000'],
            'contactable' => ['nullable'],
            'estimated_arrival' => ['nullable', 'date_format:H:i'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'half_day_type' => ['nullable', 'in:Datang terlambat,Pulang lebih awal,Keluar kantor sementara'],
            'duration' => ['nullable', 'numeric', 'min:0.1', 'max:365'],
            'agreement' => ['nullable'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $now = Carbon::now('Asia/Jakarta');
            $today = $now->toDateString();
            $tomorrow = Carbon::tomorrow('Asia/Jakarta')->toDateString();
            $minLeaveDate = $now->copy()->addDays(7)->toDateString();

            $type = $this->input('type');
            $leaveDate = $this->input('leave_date');
            $emergency = filter_var($this->input('emergency'), FILTER_VALIDATE_BOOLEAN);
            $emergencyReason = trim((string) $this->input('emergency_reason'));

            // Guards against Carbon::createFromFormat() throwing on malformed input;
            // the `date_format:H:i` rule already flags an invalid value on its own.
            $isValidTime = fn ($value): bool => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value);

            // Phone normalization check
            $phone = preg_replace('/[^\d]/', '', (string) $this->input('phone'));
            if (strlen($phone) < 8) {
                $validator->errors()->add('phone', 'Nomor telepon / WhatsApp tidak valid.');
            }

            if ($type === 'late') {
                // Izin Terlambat
                if ($leaveDate !== $today) {
                    $validator->errors()->add('leave_date', 'Izin terlambat hanya berlaku untuk tanggal hari ini.');
                }

                $arrival = $this->input('estimated_arrival');
                if (empty($arrival)) {
                    $validator->errors()->add('estimated_arrival', 'Estimasi jam kedatangan wajib diisi.');
                } elseif ($isValidTime($arrival) && $arrival > '08:30') {
                    // Jam kerja kantor dimulai pukul 08.30 WIB; keterlambatan mengikuti
                    // batas durasi maksimal izin setengah hari (4 jam).
                    $lateDurationHours = abs(Carbon::createFromFormat('H:i', $arrival)
                        ->diffInMinutes(Carbon::createFromFormat('H:i', '08:30'))) / 60;

                    if ($lateDurationHours > 4) {
                        $validator->errors()->add('estimated_arrival', 'Estimasi kedatangan lebih dari pukul 12.30 WIB (keterlambatan lebih dari 4 jam). Silakan ajukan sebagai Izin Setengah Hari.');
                    }
                }

                if (empty(trim((string) $this->input('reason')))) {
                    $validator->errors()->add('reason', 'Alasan keterlambatan wajib diisi.');
                }

                if (! $this->has('agreement')) {
                    $validator->errors()->add('agreement', 'Anda harus menyetujui pernyataan persetujuan.');
                }

                // Setelah pukul 07.00 WIB, wajib kondisi darurat
                if ($now->format('H:i') > '07:00') {
                    if (! $emergency) {
                        $validator->errors()->add('emergency', 'Pengajuan izin terlambat setelah pukul 07.00 WIB hanya diperbolehkan untuk kondisi darurat.');
                    }
                    if (empty($emergencyReason)) {
                        $validator->errors()->add('emergency_reason', 'Alasan kondisi darurat wajib diisi jika diajukan setelah pukul 07.00 WIB.');
                    }
                }
            } elseif ($type === 'half_day') {
                // Izin Setengah Hari
                if ($leaveDate < $tomorrow) {
                    $validator->errors()->add('leave_date', 'Pengajuan izin setengah hari minimal diajukan H-1.');
                }

                if (empty($this->input('half_day_type'))) {
                    $validator->errors()->add('half_day_type', 'Jenis izin setengah hari wajib dipilih.');
                }

                $startTime = $this->input('start_time');
                $endTime = $this->input('end_time');

                if (empty($startTime)) {
                    $validator->errors()->add('start_time', 'Jam mulai izin wajib diisi.');
                }
                if (empty($endTime)) {
                    $validator->errors()->add('end_time', 'Jam selesai izin wajib diisi.');
                }

                if ($startTime && $endTime && $isValidTime($startTime) && $isValidTime($endTime)) {
                    if ($endTime <= $startTime) {
                        $validator->errors()->add('end_time', 'Jam selesai izin harus lebih besar daripada jam mulai.');
                    } else {
                        $durationHours = abs(Carbon::createFromFormat('H:i', $endTime)
                            ->diffInMinutes(Carbon::createFromFormat('H:i', $startTime))) / 60;

                        if ($durationHours > 4) {
                            $validator->errors()->add('end_time', 'Durasi izin setengah hari maksimal 4 jam.');
                        }
                    }
                }

                if (empty(trim((string) $this->input('reason')))) {
                    $validator->errors()->add('reason', 'Alasan izin setengah hari wajib diisi.');
                }
            } elseif ($type === 'leave') {
                // Cuti
                if ($leaveDate < $minLeaveDate) {
                    $validator->errors()->add('leave_date', 'Pengajuan cuti harus dilakukan minimal H-7 sebelum tanggal cuti.');
                }

                if (empty(trim((string) $this->input('reason')))) {
                    $validator->errors()->add('reason', 'Alasan cuti wajib diisi.');
                }
            } elseif ($type === 'emergency') {
                // Izin Darurat (Hari H only, max 08:30:00 WIB — jam kerja kantor mulai).
                // Lewat batas ini dianggap Alpha dan tidak dapat dialihkan ke jenis izin lain.
                if ($leaveDate !== $today) {
                    $validator->errors()->add('leave_date', 'Izin darurat hanya dapat diajukan untuk tanggal hari ini.');
                }

                if ($now->format('H:i:s') > '08:30:00') {
                    $validator->errors()->add('type', 'Pengajuan izin darurat maksimal diajukan pukul 08.30 WIB. Lewat batas waktu ini, ketidakhadiran dianggap Alpha dan tidak dapat diajukan sebagai jenis izin apa pun.');
                }

                if (empty(trim((string) $this->input('reason')))) {
                    $validator->errors()->add('reason', 'Alasan izin darurat wajib diisi.');
                }
            } elseif ($type === 'sick') {
                // Izin Sakit
                if ($leaveDate < $today) {
                    $validator->errors()->add('leave_date', 'Tanggal izin sakit tidak boleh sebelum hari ini.');
                }

                // Jam kerja kantor dimulai pukul 08.30 WIB
                if ($leaveDate === $today && $now->format('H:i') >= '08:30') {
                    if (! $emergency) {
                        $validator->errors()->add('emergency', 'Pengajuan izin sakit pada hari H setelah jam kerja dimulai (08.30 WIB) hanya diperbolehkan untuk kondisi darurat.');
                    }
                    if (empty($emergencyReason)) {
                        $validator->errors()->add('emergency_reason', 'Alasan kondisi darurat wajib diisi jika melapor setelah pukul 08.30 WIB.');
                    }
                }

                $duration = (float) $this->input('duration', 1);
                if ($duration <= 0) {
                    $validator->errors()->add('duration', 'Estimasi lama istirahat wajib lebih dari 0.');
                }

                if (empty(trim((string) $this->input('reason')))) {
                    $validator->errors()->add('reason', 'Keluhan sakit wajib diisi.');
                }

                if ($this->input('contactable') === null) {
                    $validator->errors()->add('contactable', 'Pilihan apakah dapat dihubungi untuk koordinasi mendesak wajib dipilih.');
                }

                // Surat dokter wajib jika sakit > 1 hari
                if ($duration > 1) {
                    $hasAttachments = $this->hasFile('attachments') || $this->hasFile('attachment');
                    if (! $hasAttachments) {
                        $validator->errors()->add('attachments', 'Surat dokter wajib diunggah untuk estimasi sakit lebih dari 1 hari.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email pribadi wajib diisi.',
            'email.email' => 'Format email pribadi tidak valid.',
            'phone.required' => 'Nomor telepon / WhatsApp wajib diisi.',
            'department.required' => 'Departemen wajib dipilih.',
            'department.in' => 'Departemen harus General Solusindo atau Tabinaco.',
            'position.required' => 'Jabatan / divisi wajib diisi.',
            'type.required' => 'Jenis izin wajib dipilih.',
            'leave_date.required' => 'Tanggal izin wajib diisi.',
            'attachments.*.mimes' => 'Format file pendukung harus berupa PDF, JPG, JPEG, atau PNG.',
            'attachments.*.max' => 'Ukuran file pendukung maksimal 5 MB.',
            'attachment.mimes' => 'Format file pendukung harus berupa PDF, JPG, JPEG, atau PNG.',
            'attachment.max' => 'Ukuran file pendukung maksimal 5 MB.',
        ];
    }
}
