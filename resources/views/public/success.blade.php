@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4" x-data="{ copied: false }">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        <!-- Success Icon -->
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
            Pengajuan Izin Berhasil Dikirim!
        </h1>
        <p class="text-sm text-slate-700 mt-2 max-w-md mx-auto">
            Formulir perizinan Anda telah masuk ke sistem dan saat ini menunggu peninjauan dari tim HRD atau Admin.
        </p>

        <!-- Request Number Card -->
        <div class="mt-6 bg-slate-50 border border-slate-200 rounded-xl p-6 max-w-lg mx-auto">
            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-1">
                Nomor Pengajuan Unik Anda:
            </span>
            <div class="flex items-center justify-center space-x-3 mt-2">
                <span class="text-2xl sm:text-3xl font-mono font-extrabold text-indigo-700 tracking-wider">
                    {{ $leaveRequest->request_number }}
                </span>
                <button type="button" 
                    @click="navigator.clipboard.writeText('{{ $leaveRequest->request_number }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                    <svg x-show="!copied" class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" class="w-4 h-4 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                </button>
            </div>
            <p class="text-xs text-slate-700 mt-2">
                Simpan nomor pengajuan ini untuk memeriksa status persetujuan tanpa perlu login.
            </p>
        </div>

        <!-- Summary Details Table -->
        <div class="mt-6 border-t border-slate-100 pt-6 text-left max-w-lg mx-auto">
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-700">Nama Pengaju</dt>
                    <dd class="font-medium text-slate-900">{{ $leaveRequest->name }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-700">Departemen</dt>
                    <dd class="font-medium text-slate-900">{{ $leaveRequest->department }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-700">Jenis Izin</dt>
                    <dd class="font-medium text-slate-900">{{ $leaveRequest->type_label }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-700">Tanggal Izin</dt>
                    <dd class="font-medium text-slate-900">{{ \Carbon\Carbon::parse($leaveRequest->leave_date)->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-700">Waktu Pengajuan</dt>
                    <dd class="font-medium text-slate-900">{{ $leaveRequest->created_at->translatedFormat('d F Y, H:i') }} WIB</dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-700">Status Saat Ini</dt>
                    <dd>
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold {{ $leaveRequest->getStatusBadgeClasses() }}">
                            {{ $leaveRequest->status_label }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- CTA Buttons -->
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('status.index') }}"
                class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 rounded-xl border border-indigo-600 bg-indigo-50 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Cek Status Pengajuan
            </a>
            <a href="{{ route('public.form') }}"
                class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                Buat Pengajuan Baru
            </a>
        </div>
    </div>
</div>
@endsection
