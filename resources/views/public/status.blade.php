@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-4 sm:py-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-12 h-12 bg-indigo-100 text-indigo-700 rounded-xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
            Cek Status Pengajuan Izin
        </h1>
        <p class="text-sm text-slate-700 mt-1 max-w-md mx-auto">
            Masukkan nomor pengajuan unik dan email pribadi yang Anda daftarkan saat pengajuan.
        </p>
    </div>

    <!-- Search Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs mb-8">
        <form method="POST" action="{{ route('status.check') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf

            <div>
                <label for="request_number" class="block text-sm font-medium text-slate-700 mb-1">
                    Nomor Pengajuan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="request_number" name="request_number" required
                    value="{{ old('request_number', $inputNumber ?? '') }}"
                    placeholder="Contoh: IZN-2026-000001"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 uppercase font-mono placeholder:font-sans focus:ring-2 focus:ring-indigo-600 text-sm">
                @error('request_number') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                    Email Pribadi <span class="text-rose-500">*</span>
                </label>
                <input type="email" id="email" name="email" required
                    value="{{ old('email', $inputEmail ?? '') }}"
                    placeholder="budi@gmail.com"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-600 text-sm">
                @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2 pt-2">
                <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-lg border border-transparent bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-xs">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Periksa Status
                </button>
            </div>
        </form>
    </div>

    <!-- Results Section -->
    @if(isset($searched))
        @if($notFound)
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-6 text-center shadow-xs">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-rose-900">Data Pengajuan Tidak Ditemukan</h3>
                <p class="text-sm text-rose-700 mt-1 max-w-md mx-auto">
                    Mohon periksa kembali nomor pengajuan (<strong>{{ $inputNumber }}</strong>) dan alamat email pribadi Anda. Pastikan kombinasi keduanya sesuai dengan yang Anda daftarkan.
                </p>
            </div>
        @elseif(isset($statusData))
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <!-- Status Header Banner -->
                <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                    <div>
                        <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block">Nomor Pengajuan</span>
                        <span class="text-xl font-mono font-bold text-slate-900">{{ $statusData['request_number'] }}</span>
                    </div>
                    <div>
                        @if($statusData['status'] === 'approved')
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-2xs">
                                <svg class="w-4 h-4 mr-1.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                Disetujui
                            </span>
                        @elseif($statusData['status'] === 'rejected')
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-rose-600 text-white shadow-2xs">
                                <svg class="w-4 h-4 mr-1.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Ditolak
                            </span>
                        @else
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-500 text-white shadow-2xs">
                                <svg class="w-4 h-4 mr-1.5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $statusData['status_label'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Detail Body (Sanitized Non-Sensitive) -->
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs font-semibold text-slate-700 uppercase">Nama Pengaju</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $statusData['name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-700 uppercase">Jenis Izin</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $statusData['type_label'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-700 uppercase">Tanggal Izin</dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                {{ \Carbon\Carbon::parse($statusData['leave_date'])->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                        @if($statusData['duration'])
                            <div>
                                <dt class="text-xs font-semibold text-slate-700 uppercase">Durasi</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    {{ $statusData['duration'] }} {{ in_array($statusData['type'], ['half_day', 'temporary_exit'], true) ? 'Jam' : 'Hari' }}
                                </dd>
                            </div>
                        @endif
                        @if($statusData['start_time'] && $statusData['end_time'])
                            <div>
                                <dt class="text-xs font-semibold text-slate-700 uppercase">Rentang Waktu</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $statusData['start_time'] }} - {{ $statusData['end_time'] }} WIB</dd>
                            </div>
                        @endif
                        @if($statusData['type'] === 'early_departure')
                            <div>
                                <dt class="text-xs font-semibold text-slate-700 uppercase">Jam Rencana Pulang</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $statusData['start_time'] }} WIB</dd>
                            </div>
                        @endif
                        @if($statusData['estimated_arrival'])
                            <div>
                                <dt class="text-xs font-semibold text-slate-700 uppercase">Estimasi Kedatangan</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $statusData['estimated_arrival'] }} WIB</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-semibold text-slate-700 uppercase">Waktu Pengajuan</dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                {{ \Carbon\Carbon::parse($statusData['created_at'])->translatedFormat('d F Y, H:i') }} WIB
                            </dd>
                        </div>
                        @if($statusData['processed_at'])
                            <div>
                                <dt class="text-xs font-semibold text-slate-700 uppercase">Diproses Pada</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    {{ \Carbon\Carbon::parse($statusData['processed_at'])->translatedFormat('d F Y, H:i') }} WIB
                                    @if($statusData['processor_role'])
                                        <span class="text-xs text-slate-700">({{ $statusData['processor_role'] }})</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if($statusData['status'] === 'rejected' && $statusData['rejection_reason'])
                        <div class="mt-6 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                            <span class="text-xs font-bold text-rose-800 uppercase tracking-wider block mb-1">
                                Alasan Penolakan:
                            </span>
                            <p class="text-sm text-rose-900 font-medium whitespace-pre-line">
                                {{ $statusData['rejection_reason'] }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-700">
                    <span>Pemberitahuan resmi juga dikirimkan ke email Anda.</span>
                    <a href="{{ route('public.form') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Ajukan Izin Lain &rarr;
                    </a>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
