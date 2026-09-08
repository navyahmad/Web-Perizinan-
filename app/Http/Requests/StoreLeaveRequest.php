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
            'position' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:late,half_day,leave,personal,sick'],
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
                } elseif ($arrival > '09:30') {
                    $validator->errors()->add('estimated_arrival', 'Estimasi kedatangan maksimal adalah pukul 09.30 WIB.');
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

                if ($startTime && $endTime && $endTime <= $startTime) {
                    $validator->errors()->add('end_time', 'Jam selesai izin harus lebih besar daripada jam mulai.');
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
            } elseif ($type === 'personal') {
                // Izin Pribadi
                if (empty(trim((string) $this->input('reason')))) {
                    $validator->errors()->add('reason', 'Alasan izin pribadi wajib diisi.');
                }

                if ($leaveDate === $today) {
                    if (! $emergency) {
                        $validator->errors()->add('emergency', 'Izin pribadi pada hari H hanya diperbolehkan untuk kondisi mendesak.');
                    }
                    if (empty($emergencyReason)) {
                        $validator->errors()->add('emergency_reason', 'Alasan kondisi mendesak wajib diisi untuk pengajuan hari H.');
                    }
                } elseif ($leaveDate < $tomorrow) {
                    $validator->errors()->add('leave_date', 'Pengajuan izin pribadi normal minimal dilakukan H-1.');
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
