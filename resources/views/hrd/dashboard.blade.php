@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Dashboard Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard HRD</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                    Tim HRD
                </span>
            </div>
            <p class="text-sm text-slate-700 mt-0.5">
                Pusat peninjauan dan persetujuan pengajuan izin kerja karyawan.
            </p>
        </div>
        <div>
            <a href="{{ route('requests.index', ['status' => 'pending']) }}"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold shadow-xs transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Menunggu HRD ({{ $stats['pending'] }})
            </a>
        </div>
    </div>

    <!-- KPI Statistics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block">Total Pengajuan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</span>
                <span class="text-xs text-slate-600 font-medium">Keseluruhan</span>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white p-5 rounded-xl border border-amber-200 shadow-2xs bg-amber-50/20">
            <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider block">Menunggu HRD</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-amber-600">{{ number_format($stats['pending']) }}</span>
                <span class="text-xs text-amber-700 font-medium">Pending</span>
            </div>
        </div>

        <!-- Approved -->
        <div class="bg-white p-5 rounded-xl border border-emerald-200 shadow-2xs bg-emerald-50/20">
            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Disetujui</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($stats['approved']) }}</span>
                <span class="text-xs text-emerald-700 font-medium">Approved</span>
            </div>
        </div>

        <!-- Rejected -->
        <div class="bg-white p-5 rounded-xl border border-rose-200 shadow-2xs bg-rose-50/20">
            <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider block">Ditolak</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-rose-600">{{ number_format($stats['rejected']) }}</span>
                <span class="text-xs text-rose-700 font-medium">Rejected</span>
            </div>
        </div>

        <!-- Today -->
        <div class="bg-white p-5 rounded-xl border border-indigo-200 shadow-2xs bg-indigo-50/20 col-span-2 sm:col-span-1">
            <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider block">Hari Ini</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-indigo-600">{{ number_format($stats['today']) }}</span>
                <span class="text-xs text-indigo-700 font-medium">Masuk hari ini</span>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Filter Toolbar -->
        <div class="p-4 border-b border-slate-200 bg-slate-50/50">
            <form method="GET" action="{{ route('hrd.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / nomor..."
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                </div>

                <div>
                    <select name="status" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                        <option value="">-- Semua Status --</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu HRD</option>
                        <option value="pending_manager" {{ request('status') === 'pending_manager' ? 'selected' : '' }}>Menunggu Manager</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <div>
                    <select name="type" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                        <option value="">-- Semua Jenis Izin --</option>
                        <option value="late" {{ request('type') === 'late' ? 'selected' : '' }}>Izin Terlambat</option>
                        <option value="half_day" {{ request('type') === 'half_day' ? 'selected' : '' }}>Izin Setengah Hari</option>
                        <option value="early_departure" {{ request('type') === 'early_departure' ? 'selected' : '' }}>Izin Pulang Lebih Awal</option>
                        <option value="temporary_exit" {{ request('type') === 'temporary_exit' ? 'selected' : '' }}>Izin Keluar Kantor Sebentar</option>
                        <option value="leave" {{ request('type') === 'leave' ? 'selected' : '' }}>Cuti</option>
                        <option value="personal" {{ request('type') === 'personal' ? 'selected' : '' }}>Izin Pribadi</option>
                        <option value="sick" {{ request('type') === 'sick' ? 'selected' : '' }}>Izin Sakit</option>
                    </select>
                </div>

                <div>
                    <select name="department" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                        <option value="">-- Semua Departemen --</option>
                        <option value="General Solusindo" {{ request('department') === 'General Solusindo' ? 'selected' : '' }}>General Solusindo</option>
                        <option value="Tabinaco" {{ request('department') === 'Tabinaco' ? 'selected' : '' }}>Tabinaco</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full text-xs font-semibold py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        Terapkan
                    </button>
                    @if(request()->hasAny(['search', 'status', 'type', 'department']))
                        <a href="{{ route('hrd.dashboard') }}" class="text-xs py-2 px-3 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">No. Pengajuan</th>
                        <th class="px-4 py-3">Nama Pengaju</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Jenis Izin</th>
                        <th class="px-4 py-3">Tanggal Izin</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $req)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-mono font-bold text-indigo-700">
                                <a href="{{ route('requests.show', $req->id) }}" class="hover:underline">
                                    {{ $req->request_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-900 block">{{ $req->name }}</span>
                                <span class="text-xs text-slate-700">{{ $req->position }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $req->department }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="font-medium text-slate-800">{{ $req->type_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ \Carbon\Carbon::parse($req->leave_date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $req->getStatusBadgeClasses() }}">
                                    {{ $req->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('requests.show', $req->id) }}"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                Tidak ada data pengajuan yang sesuai dengan kriteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
