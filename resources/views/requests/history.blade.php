@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Riwayat Pengajuan Izin</h1>
            <p class="text-sm text-slate-700 mt-0.5">
                Arsip lengkap seluruh permohonan izin yang pernah diajukan ke kantor.
            </p>
        </div>
        <div>
            <a href="{{ route('reports.export', request()->query()) }}"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-2xs transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Riwayat (CSV)
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4">
        <form method="GET" action="{{ route('requests.history') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / No. Pengajuan..."
                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                <select name="status" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                    <option value="">-- Semua Status --</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu HRD</option>
                    <option value="pending_manager" {{ request('status') === 'pending_manager' ? 'selected' : '' }}>Menunggu Manager</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Izin</label>
                <select name="type" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                    <option value="">-- Semua Jenis --</option>
                    <option value="late" {{ request('type') === 'late' ? 'selected' : '' }}>Izin Terlambat</option>
                    <option value="half_day" {{ request('type') === 'half_day' ? 'selected' : '' }}>Izin Setengah Hari</option>
                    <option value="early_departure" {{ request('type') === 'early_departure' ? 'selected' : '' }}>Izin Pulang Lebih Awal</option>
                    <option value="temporary_exit" {{ request('type') === 'temporary_exit' ? 'selected' : '' }}>Izin Keluar Kantor Sebentar</option>
                    <option value="leave" {{ request('type') === 'leave' ? 'selected' : '' }}>Cuti</option>
                    <option value="emergency" {{ request('type') === 'emergency' ? 'selected' : '' }}>Izin Darurat</option>
                    <option value="sick" {{ request('type') === 'sick' ? 'selected' : '' }}>Izin Sakit</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Departemen</label>
                <select name="department" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
                    <option value="">-- Semua Departemen --</option>
                    <option value="General Solusindo" {{ request('department') === 'General Solusindo' ? 'selected' : '' }}>General Solusindo</option>
                    <option value="Tabinaco" {{ request('department') === 'Tabinaco' ? 'selected' : '' }}>Tabinaco</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full text-xs font-semibold py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition h-[35px]">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'type', 'department', 'start_date', 'end_date']))
                    <a href="{{ route('requests.history') }}" class="text-xs py-2 px-3 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition h-[35px] flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">No. Pengajuan</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Jenis Izin</th>
                        <th class="px-4 py-3">Tanggal Izin</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Diproses Oleh</th>
                        <th class="px-4 py-3">Waktu Proses</th>
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
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $req->name }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $req->department }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $req->type_label }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ \Carbon\Carbon::parse($req->leave_date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $req->getStatusBadgeClasses() }}">
                                    {{ $req->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($req->processor)
                                    <span class="font-medium text-slate-800">{{ $req->processor->name }}</span>
                                    <span class="text-2xs text-slate-700 block">({{ strtoupper($req->processor->role) }})</span>
                                @else
                                    <span class="text-slate-700">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-700">
                                {{ $req->processed_at ? $req->processed_at->translatedFormat('d M Y, H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('requests.show', $req->id) }}"
                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">
                                Tidak ada data riwayat yang ditemukan.
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
