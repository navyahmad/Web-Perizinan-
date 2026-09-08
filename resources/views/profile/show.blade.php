@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Profil Akun Staf</h1>
        <p class="text-sm text-slate-700 mt-0.5">
            Informasi akun dan riwayat aktivitas persetujuan izin Anda.
        </p>
    </div>

    <!-- Profile Info Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 sm:p-8 flex items-center space-x-5 border-b border-slate-100">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs">
                {{ substr($user->name, 0, 2) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $user->role }}
                    </span>
                </div>
                <p class="text-sm text-slate-700 mt-0.5">{{ $user->email }}</p>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-700 uppercase">Peran / Otoritas</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        {{ $user->isAdmin() ? 'Administrator (Role Tertinggi)' : 'Tim HRD (Approval Izin)' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-700 uppercase">Nomor Kontak</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $user->phone ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-700 uppercase">Waktu Bergabung</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $user->created_at->translatedFormat('d F Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-700 uppercase">Zona Waktu Sistem</dt>
                    <dd class="mt-1 font-medium text-slate-900">WIB (Asia/Jakarta)</dd>
                </div>
            </dl>

            <!-- Activity Stats -->
            <div class="mt-8 pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">
                    Statistik Keputusan yang Diproses
                </h3>

                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <span class="text-xs text-slate-700 font-medium block">Total Diproses</span>
                        <span class="text-2xl font-bold text-slate-900 mt-1 block">{{ $processedCount }}</span>
                    </div>

                    <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-200">
                        <span class="text-xs text-emerald-700 font-medium block">Disetujui</span>
                        <span class="text-2xl font-bold text-emerald-600 mt-1 block">{{ $approvedCount }}</span>
                    </div>

                    <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-200">
                        <span class="text-xs text-rose-700 font-medium block">Ditolak</span>
                        <span class="text-2xl font-bold text-rose-600 mt-1 block">{{ $rejectedCount }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
