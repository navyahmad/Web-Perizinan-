@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto pb-8"
     x-data="{
        type: '{{ old('type', 'late') ?: 'late' }}',
        emergency: {{ old('emergency') ? 'true' : 'false' }},
        estimatedArrival: '{{ old('estimated_arrival', '08:45') ?: '08:45' }}',
        startTime: '{{ old('start_time', '08:30') ?: '08:30' }}',
        endTime: '{{ old('end_time', '12:30') ?: '12:30' }}',
        sickDuration: {{ is_numeric(old('duration')) ? (float) old('duration') : 1 }},
        todayDate: '{{ \Carbon\Carbon::now('Asia/Jakarta')->toDateString() }}',
        tomorrowDate: '{{ \Carbon\Carbon::tomorrow('Asia/Jakarta')->toDateString() }}',
        minLeaveDate: '{{ \Carbon\Carbon::now('Asia/Jakarta')->addDays(7)->toDateString() }}',
        currentTime: '{{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i') }}',
        selectedFiles: [],
        
        get calculatedHalfDayHours() {
            if (!this.startTime || !this.endTime) return 0;
            const [sh, sm] = this.startTime.split(':').map(Number);
            const [eh, em] = this.endTime.split(':').map(Number);
            const startMinutes = sh * 60 + sm;
            const endMinutes = eh * 60 + em;
            if (endMinutes <= startMinutes) return 0;
            return ((endMinutes - startMinutes) / 60).toFixed(1);
        },

        get isPast7Am() {
            return this.currentTime > '07:00';
        },

        get isPast830Am() {
            return this.currentTime >= '08:30';
        },

        get isSubmitBlocked() {
            if (this.type === 'late' && this.estimatedArrival > '09:30') return true;
            if (this.type === 'half_day' && this.calculatedHalfDayHours > 4) return true;
            if (this.type === 'emergency' && this.isPast830Am) return true;
            return false;
        }
     }">

    <!-- Form Header (Responsive on all screen sizes) -->
    <div class="bg-white rounded-t-2xl border border-b-0 border-slate-200 p-5 sm:p-8 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <span class="inline-flex items-center self-start px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap shadow-2xs">
                <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Formulir Resmi Pengajuan Izin
            </span>
            <div class="inline-flex items-center text-xs text-slate-600 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200 self-start sm:self-auto whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse shrink-0"></span>
                <span>WIB (Asia/Jakarta): <strong class="text-slate-900 font-semibold">{{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i') }} WIB</strong></span>
            </div>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-3 sm:mt-4 tracking-tight">
            Pengajuan Izin Kantor
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 mt-1 sm:mt-1.5 leading-relaxed">
            Silakan lengkapi data di bawah ini. Pengajuan Anda akan ditinjau langsung oleh tim HRD atau Admin.
        </p>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-b-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <form method="POST" action="{{ route('public.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200">
                    <div class="flex">
                        <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-rose-800">Mohon periksa kembali formulir Anda:</h3>
                            <ul class="mt-2 text-xs text-rose-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- SECTION 1: Data Pengaju -->
            <div>
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4 flex items-center">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mr-2">1</span>
                    Data Pengaju
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nama Lengkap -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" required value="{{ old('name') }}"
                            placeholder="Contoh: Budi Santoso"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Email Pribadi -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                            Email Pribadi <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}"
                            placeholder="budi@gmail.com"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                        <p class="text-xs text-slate-700 mt-1">Digunakan untuk pemberitahuan persetujuan dan cek status.</p>
                        @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Nomor Telepon / WhatsApp -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">
                            Nomor WhatsApp <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel" id="phone" name="phone" required value="{{ old('phone') }}"
                            placeholder="081234567890"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                        <p class="text-xs text-slate-700 mt-1">Format: 0812... atau 628...</p>
                        @error('phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Departemen -->
                    <div>
                        <label for="department" class="block text-sm font-medium text-slate-700 mb-1">
                            Departemen <span class="text-rose-500">*</span>
                        </label>
                        <select id="department" name="department" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 text-sm bg-white">
                            <option value="">-- Pilih Departemen --</option>
                            <option value="General Solusindo" {{ old('department') == 'General Solusindo' ? 'selected' : '' }}>General Solusindo</option>
                            <option value="Tabinaco" {{ old('department') == 'Tabinaco' ? 'selected' : '' }}>Tabinaco</option>
                        </select>
                        @error('department') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Jabatan/Posisi -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-slate-700 mb-1">
                            Jabatan / Divisi <span class="text-rose-500">*</span>
                        </label>
                        <select id="position" name="position" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 text-sm bg-white">
                            <option value="">-- Pilih Jabatan / Posisi --</option>
                            @php
                                $positions = [
                                    'Sales',
                                    'Marketing',
                                    'Finance',
                                    'Operasional',
                                    'Procurement',
                                    'Teknisi',
                                    'Internship',
                                    'Admin Store',
                                    'Content Creator',
                                    'HR',
                                    'Digital Merketing',
                                    'SI Officer',
                                ];
                            @endphp
                            @foreach($positions as $pos)
                                <option value="{{ $pos }}" {{ old('position') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                            @endforeach
                        </select>
                        @error('position') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Jenis Izin -->
            <div>
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4 flex items-center">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mr-2">2</span>
                    Jenis Izin
                </h3>

                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 mb-1">
                        Pilih Jenis Izin <span class="text-rose-500">*</span>
                    </label>
                    <select id="type" name="type" x-model="type" required
                        class="w-full rounded-lg border-2 border-indigo-500 px-4 py-2.5 text-slate-900 font-semibold focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 text-sm bg-indigo-50/20">
                        <option value="late">Izin Terlambat</option>
                        <option value="half_day">Izin Setengah Hari</option>
                        <option value="leave">Cuti</option>
                        <option value="emergency">Izin Darurat</option>
                        <option value="sick">Izin Sakit</option>
                    </select>
                    @error('type') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- SECTION 3: Detail Izin Dinamis (Alpine.js) -->
            <div>
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4 flex items-center">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mr-2">3</span>
                    Detail Izin
                </h3>

                <!-- 3.1 TYPE: LATE (Izin Terlambat) -->
                <div x-show="type === 'late'" class="space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-3 text-xs text-amber-900 rounded-r">
                        <strong>Ketentuan Izin Terlambat:</strong>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li>Hanya berlaku untuk tanggal hari ini.</li>
                            <li>Batas pengajuan normal maksimal pukul <strong>07.00 WIB</strong>.</li>
                            <li>Setelah pukul 07.00 WIB hanya diizinkan untuk kondisi darurat.</li>
                            <li>Estimasi kedatangan maksimal pukul <strong>09.30 WIB</strong>.</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Tanggal Terlambat <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="leave_date" :value="todayDate" value="{{ \Carbon\Carbon::now('Asia/Jakarta')->toDateString() }}" readonly
                                :disabled="type !== 'late'" {{ old('type', 'late') !== 'late' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 bg-slate-100 text-slate-600 text-sm cursor-not-allowed">
                        </div>

                        <div>
                            <label for="estimated_arrival" class="block text-sm font-medium text-slate-700 mb-1">
                                Estimasi Jam Kedatangan <span class="text-rose-500">*</span>
                            </label>
                            <input type="time" id="estimated_arrival" name="estimated_arrival" max="09:30"
                                x-model="estimatedArrival"
                                :disabled="type !== 'late'" {{ old('type', 'late') !== 'late' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            <p class="text-xs text-slate-700 mt-1">Maksimal pukul 09.30 WIB.</p>
                            @error('estimated_arrival') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div x-show="estimatedArrival > '09:30'"
                         class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                        ⚠️ <strong>Melebihi Batas:</strong> Estimasi kedatangan melebihi pukul 09.30 WIB. Pengajuan tidak dapat dikirim.
                    </div>

                    <!-- Emergency trigger if after 07.00 -->
                    <div class="pt-2">
                        <label class="flex items-center text-sm font-medium text-slate-700">
                            <input type="checkbox" name="emergency" value="1" x-model="emergency"
                                :disabled="type !== 'late'" {{ old('type', 'late') !== 'late' ? 'disabled' : '' }}
                                class="h-4 w-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <span class="ml-2">Kondisi Darurat (Wajib dicentang jika pengajuan setelah pukul 07.00 WIB)</span>
                        </label>
                    </div>

                    <div x-show="emergency || isPast7Am" class="space-y-1">
                        <label for="emergency_reason_late" class="block text-sm font-medium text-rose-700">
                            Alasan Kondisi Darurat <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="emergency_reason_late" name="emergency_reason" rows="2"
                            :disabled="type !== 'late'" {{ old('type', 'late') !== 'late' ? 'disabled' : '' }}
                            placeholder="Jelaskan kondisi darurat yang menyebabkan keterlambatan..."
                            class="w-full rounded-lg border border-rose-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-rose-500 text-sm bg-rose-50/30">{{ old('emergency_reason') }}</textarea>
                        @error('emergency_reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason_late" class="block text-sm font-medium text-slate-700 mb-1">
                            Alasan Keterlambatan <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason_late" name="reason" rows="3" placeholder="Sebutkan penyebab keterlambatan..."
                            :disabled="type !== 'late'" {{ old('type', 'late') !== 'late' ? 'disabled' : '' }}
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2 border-t border-slate-200">
                        <label class="flex items-start text-xs text-slate-700">
                            <input type="checkbox" name="agreement" value="1" checked
                                :disabled="type !== 'late'" {{ old('type', 'late') !== 'late' ? 'disabled' : '' }}
                                class="h-4 w-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 mt-0.5">
                            <span class="ml-2 font-medium">Saya memahami pengajuan ini memerlukan persetujuan HRD atau Admin. <span class="text-rose-500">*</span></span>
                        </label>
                        @error('agreement') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- 3.2 TYPE: HALF DAY (Izin Setengah Hari) -->
                <div x-show="type === 'half_day'" class="space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 text-xs text-blue-900 rounded-r">
                        <strong>Ketentuan Izin Setengah Hari:</strong>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li>Pengajuan minimal <strong>H-1</strong> (tanggal izin mulai besok).</li>
                            <li>Ketidakhadiran maksimal <strong>4 jam kerja</strong> dari total jam kerja harian.</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Tanggal Izin Setengah Hari <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="leave_date" :min="tomorrowDate" value="{{ old('leave_date') }}"
                                :disabled="type !== 'half_day'" {{ old('type', 'late') !== 'half_day' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            <p class="text-xs text-slate-700 mt-1">Minimal tanggal besok (H-1).</p>
                            @error('leave_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Jenis Izin Setengah Hari <span class="text-rose-500">*</span>
                            </label>
                            <select name="half_day_type"
                                :disabled="type !== 'half_day'" {{ old('type', 'late') !== 'half_day' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm bg-white">
                                <option value="Datang terlambat" {{ old('half_day_type') == 'Datang terlambat' ? 'selected' : '' }}>Datang terlambat</option>
                                <option value="Pulang lebih awal" {{ old('half_day_type') == 'Pulang lebih awal' ? 'selected' : '' }}>Pulang lebih awal</option>
                                <option value="Keluar kantor sementara" {{ old('half_day_type') == 'Keluar kantor sementara' ? 'selected' : '' }}>Keluar kantor sementara</option>
                            </select>
                            @error('half_day_type') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Jam Mulai <span class="text-rose-500">*</span>
                            </label>
                            <input type="time" name="start_time" x-model="startTime"
                                :disabled="type !== 'half_day'" {{ old('type', 'late') !== 'half_day' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            @error('start_time') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Jam Selesai <span class="text-rose-500">*</span>
                            </label>
                            <input type="time" name="end_time" x-model="endTime"
                                :disabled="type !== 'half_day'" {{ old('type', 'late') !== 'half_day' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            @error('end_time') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Automatic Duration & Warning Badge -->
                    <div class="p-3 bg-white rounded-lg border border-slate-200 flex items-center justify-between text-xs">
                        <span class="text-slate-600">Durasi Terhitung Otomatis:</span>
                        <span class="font-bold text-sm text-indigo-700" x-text="calculatedHalfDayHours + ' Jam'"></span>
                    </div>

                    <div x-show="calculatedHalfDayHours > 4"
                         class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                        ⚠️ <strong>Durasi Melebihi Batas:</strong> Durasi (<span x-text="calculatedHalfDayHours"></span> jam) melebihi batas maksimal izin setengah hari (4 jam kerja). Silakan sesuaikan jam mulai/selesai.
                    </div>

                    <div>
                        <label for="reason_half_day" class="block text-sm font-medium text-slate-700 mb-1">
                            Alasan Izin Setengah Hari <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason_half_day" name="reason" rows="3" placeholder="Jelaskan alasan izin setengah hari..."
                            :disabled="type !== 'half_day'" {{ old('type', 'late') !== 'half_day' ? 'disabled' : '' }}
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- 3.3 TYPE: LEAVE (Cuti) -->
                <div x-show="type === 'leave'" class="space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-3 text-xs text-indigo-900 rounded-r">
                        <strong>Ketentuan Cuti:</strong>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li>Pengajuan wajib minimal <strong>H-7</strong> sebelum tanggal cuti.</li>
                            <li>Durasi kalender dihitung konsisten.</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Tanggal Cuti <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="leave_date" :min="minLeaveDate" value="{{ old('leave_date') }}"
                                :disabled="type !== 'leave'" {{ old('type', 'late') !== 'leave' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            <p class="text-xs text-slate-700 mt-1">Minimal H-7: mulai tanggal <span x-text="minLeaveDate"></span>.</p>
                            @error('leave_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Durasi Cuti (Hari) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="duration" min="1" max="30" value="{{ old('duration', 1) }}"
                                :disabled="type !== 'leave'" {{ old('type', 'late') !== 'leave' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            <p class="text-xs text-slate-700 mt-1">Jumlah hari cuti kalender.</p>
                            @error('duration') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="reason_leave" class="block text-sm font-medium text-slate-700 mb-1">
                            Alasan Cuti <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason_leave" name="reason" rows="3" placeholder="Sebutkan keperluan cuti..."
                            :disabled="type !== 'leave'" {{ old('type', 'late') !== 'leave' ? 'disabled' : '' }}
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- 3.4 TYPE: EMERGENCY (Izin Darurat) -->
                <div x-show="type === 'emergency'" class="space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-3 text-xs text-amber-900 rounded-r">
                        <strong>Ketentuan Izin Darurat:</strong>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li>Izin Darurat hanya dapat diajukan pada hari yang sama dan maksimal pukul <strong>08.30 WIB</strong> (jam kerja kantor mulai).</li>
                            <li>Lewat pukul 08.30 WIB, ketidakhadiran dianggap <strong>Alpha</strong> dan tidak dapat dialihkan atau diajukan sebagai jenis izin apa pun.</li>
                            <li>Tipe izin ini merepresentasikan kondisi darurat/mendesak tanpa perlu memilih opsi terpisah.</li>
                        </ul>
                    </div>

                    <div x-show="isPast830Am" class="p-3 bg-rose-50 border border-rose-300 rounded-lg text-xs text-rose-900 flex items-center">
                        <svg class="w-5 h-5 text-rose-600 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Waktu saat ini telah melewati pukul 08.30 WIB. Ketidakhadiran dianggap Alpha dan pengajuan izin apa pun untuk hari ini akan ditolak oleh sistem.</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Tanggal Izin Darurat <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="leave_date" :value="todayDate" value="{{ \Carbon\Carbon::now('Asia/Jakarta')->toDateString() }}" readonly
                            :disabled="type !== 'emergency'" {{ old('type') !== 'emergency' ? 'disabled' : '' }}
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 bg-slate-100 text-slate-600 text-sm cursor-not-allowed">
                        <p class="text-xs text-slate-500 mt-1">Hanya berlaku untuk tanggal hari ini.</p>
                        @error('leave_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason_emergency" class="block text-sm font-medium text-slate-700 mb-1">
                            Alasan Izin Darurat <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason_emergency" name="reason" rows="3" placeholder="Jelaskan keperluan kondisi darurat Anda..."
                            :disabled="type !== 'emergency'" {{ old('type') !== 'emergency' ? 'disabled' : '' }}
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- 3.5 TYPE: SICK (Izin Sakit) -->
                <div x-show="type === 'sick'" class="space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                    <div class="bg-rose-50 border-l-4 border-rose-500 p-3 text-xs text-rose-900 rounded-r">
                        <strong>Ketentuan Izin Sakit:</strong>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li>Wajib melapor sebelum jam kerja dimulai (<strong>sebelum 08.30 WIB</strong>) kecuali kondisi darurat.</li>
                            <li>Jika melapor pada atau setelah pukul 08.30 WIB, wajib mengisi alasan kondisi darurat.</li>
                            <li><strong>Surat dokter wajib diunggah jika sakit lebih dari 1 hari.</strong></li>
                            <li>Untuk sakit 1 hari tanpa bukti medis dapat diperhitungkan sebagai izin pribadi sesuai kebijakan perusahaan.</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Tanggal Izin Sakit <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="leave_date" :min="todayDate" value="{{ old('leave_date') }}"
                                :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            @error('leave_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Estimasi Lama Istirahat (Hari) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="duration" min="1" max="14" x-model="sickDuration"
                                :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                            @error('duration') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Medical Certificate Requirement Alert -->
                    <div x-show="sickDuration > 1" class="p-3 bg-amber-50 border border-amber-300 rounded-lg text-xs text-amber-900 flex items-center">
                        <svg class="w-5 h-5 text-amber-600 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Karena estimasi istirahat <strong>lebih dari 1 hari</strong>, <strong>surat dokter wajib diunggah</strong> pada bagian dokumen pendukung.</span>
                    </div>

                    <!-- Emergency trigger if report after 08.30 WIB -->
                    <div class="pt-1">
                        <label class="flex items-center text-sm font-medium text-slate-700">
                            <input type="checkbox" name="emergency" value="1" x-model="emergency"
                                :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                                class="h-4 w-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                            <span class="ml-2">Kondisi Darurat (Wajib dicentang jika melapor pada atau setelah 08.30 WIB)</span>
                        </label>
                    </div>

                    <div x-show="emergency || isPast830Am" class="space-y-1">
                        <label for="emergency_reason_sick" class="block text-sm font-medium text-rose-700">
                            Alasan Kondisi Darurat (Melapor Lewat 08.30 WIB) <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="emergency_reason_sick" name="emergency_reason" rows="2"
                            :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                            placeholder="Jelaskan alasan kondisi darurat mengapa baru melapor sakit setelah jam kerja dimulai..."
                            class="w-full rounded-lg border border-rose-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-rose-500 text-sm bg-rose-50/30">{{ old('emergency_reason') }}</textarea>
                        @error('emergency_reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason_sick" class="block text-sm font-medium text-slate-700 mb-1">
                            Keluhan Sakit <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason_sick" name="reason" rows="3" placeholder="Sebutkan keluhan sakit yang dirasakan..."
                            :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Dapat dihubungi untuk koordinasi pekerjaan mendesak? <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center space-x-6">
                            <label class="inline-flex items-center text-sm text-slate-700">
                                <input type="radio" name="contactable" value="1" {{ old('contactable', '1') == '1' ? 'checked' : '' }}
                                    :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                                    class="h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                <span class="ml-2">Ya, dapat dihubungi</span>
                            </label>
                            <label class="inline-flex items-center text-sm text-slate-700">
                                <input type="radio" name="contactable" value="0" {{ old('contactable') === '0' ? 'checked' : '' }}
                                    :disabled="type !== 'sick'" {{ old('type', 'late') !== 'sick' ? 'disabled' : '' }}
                                    class="h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                <span class="ml-2">Tidak dapat dihubungi</span>
                            </label>
                        </div>
                        @error('contactable') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Dokumen Pendukung -->
            <div>
                <h3 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4 flex items-center">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mr-2">4</span>
                    Dokumen / Bukti Pendukung
                </h3>

                <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-indigo-400 transition bg-slate-50/50">
                    <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="mt-3 text-sm text-slate-600">
                        <label for="attachments" class="relative cursor-pointer rounded-md font-semibold text-indigo-600 hover:text-indigo-500 focus-within:outline-hidden">
                            <span>Pilih file untuk diunggah</span>
                            <input id="attachments" name="attachments[]" type="file" multiple class="sr-only" accept=".pdf,.jpg,.jpeg,.png"
                                @change="selectedFiles = Array.from($event.target.files).map(f => f.name)">
                        </label>
                        <span class="text-slate-500"> atau seret ke sini</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Format yang didukung: <strong>PDF, JPG, JPEG, PNG</strong> (Maksimal 5 MB per file).
                    </p>

                    <!-- Preview of selected files -->
                    <div x-show="selectedFiles.length > 0" class="mt-4 pt-3 border-t border-slate-200 text-left">
                        <p class="text-xs font-semibold text-slate-700 mb-2">File yang dipilih:</p>
                        <ul class="space-y-1">
                            <template x-for="(file, idx) in selectedFiles" :key="idx">
                                <li class="text-xs text-indigo-700 bg-indigo-50 border border-indigo-200 rounded px-2.5 py-1 inline-flex items-center mr-1 mb-1">
                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <span x-text="file"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                @error('attachments') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                @error('attachments.*') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Submit Button -->
            <div x-show="isSubmitBlocked" class="p-3 bg-rose-50 border border-rose-300 rounded-lg text-xs text-rose-900 flex items-center">
                <svg class="w-5 h-5 text-rose-600 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Pengajuan tidak dapat dikirim karena melebihi batas waktu/durasi yang diizinkan untuk jenis izin ini. Silakan sesuaikan data di atas.</span>
            </div>

            <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="{{ route('status.index') }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600 order-2 sm:order-1">
                    &larr; Sudah pernah mengajukan? Cek status di sini
                </a>

                <button type="submit" :disabled="isSubmitBlocked"
                    :class="isSubmitBlocked ? 'opacity-50 cursor-not-allowed bg-slate-400 hover:bg-slate-400' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 border border-transparent rounded-xl shadow-md text-base font-semibold text-white focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 transition order-1 sm:order-2">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Ajukan Izin Sekarang
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
