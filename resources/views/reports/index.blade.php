@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Laporan Izin Kantor</h1>
            <p class="text-sm text-slate-700 mt-0.5">
                Ringkasan statistik dan distribusi pengajuan izin kantor.
            </p>
        </div>
        <div>
            <a href="{{ route('reports.export', request()->query()) }}"
                class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Unduh Laporan (CSV)
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 sm:p-5">
        <div class="mb-3">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Filter Rentang Waktu</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Filter ini membatasi statistik dan unduhan CSV berdasarkan <strong>tanggal pelaksanaan izin</strong> karyawan (bukan tanggal form dikirim).
            </p>
        </div>
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Dari Tanggal Izin</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white focus:ring-2 focus:ring-indigo-600">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Sampai Tanggal Izin</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="text-xs rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white focus:ring-2 focus:ring-indigo-600">
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="text-xs font-semibold py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition h-[35px] cursor-pointer">
                    Terapkan Rentang
                </button>
                @if($startDate || $endDate)
                    <a href="{{ route('reports.index') }}" class="text-xs py-2 px-3 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition h-[35px] flex items-center justify-center">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>

        @if($startDate || $endDate)
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center text-xs text-indigo-700 font-medium">
                <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Filter aktif: 
                    @if($startDate && $endDate)
                        <strong>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
                    @elseif($startDate)
                        Mulai dari <strong>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong> ke atas
                    @elseif($endDate)
                        Sampai dengan <strong>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
                    @endif
                    ({{ $totalRequests }} data ditemukan)
                </span>
            </div>
        @endif
    </div>

    <!-- Metric Cards Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- By Status Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                Berdasarkan Status
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600 flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2"></span>
                        Menunggu (Pending)
                    </span>
                    <span class="font-bold text-slate-900">{{ number_format($byStatus['pending']) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600 flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2"></span>
                        Disetujui (Approved)
                    </span>
                    <span class="font-bold text-slate-900">{{ number_format($byStatus['approved']) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600 flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-2"></span>
                        Ditolak (Rejected)
                    </span>
                    <span class="font-bold text-slate-900">{{ number_format($byStatus['rejected']) }}</span>
                </div>
                <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-sm font-bold text-slate-900">
                    <span>Total Keseluruhan</span>
                    <span>{{ number_format($totalRequests) }}</span>
                </div>
            </div>
        </div>

        <!-- By Leave Type Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                Berdasarkan Jenis Izin
            </h3>
            <div class="space-y-2.5">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600">Izin Terlambat</span>
                    <span class="font-bold text-slate-900">{{ number_format($byType['late']) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600">Izin Setengah Hari</span>
                    <span class="font-bold text-slate-900">{{ number_format($byType['half_day']) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600">Cuti</span>
                    <span class="font-bold text-slate-900">{{ number_format($byType['leave']) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600">Izin Darurat</span>
                    <span class="font-bold text-slate-900">{{ number_format($byType['emergency']) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600">Izin Sakit</span>
                    <span class="font-bold text-slate-900">{{ number_format($byType['sick']) }}</span>
                </div>
            </div>
        </div>

        <!-- By Department Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                Berdasarkan Departemen
            </h3>
            <div class="space-y-3">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-700 uppercase font-semibold block">General Solusindo</span>
                    <span class="text-xl font-bold text-indigo-700 mt-1 block">{{ number_format($byDepartment['General Solusindo']) }}</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-700 uppercase font-semibold block">Tabinaco</span>
                    <span class="text-xl font-bold text-indigo-700 mt-1 block">{{ number_format($byDepartment['Tabinaco']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Preview -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rincian Pengajuan Dalam Laporan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftar pengajuan izin sesuai rentang tanggal yang dipilih.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                Total: {{ $totalRequests }} Pengajuan
            </span>
        </div>

        @if($records->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50/75 text-slate-600 uppercase font-semibold">
                        <tr>
                            <th class="px-5 py-3.5">No. Pengajuan</th>
                            <th class="px-5 py-3.5">Nama & Departemen</th>
                            <th class="px-5 py-3.5">Jenis Izin</th>
                            <th class="px-5 py-3.5">Tanggal Izin</th>
                            <th class="px-5 py-3.5">Durasi</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Diproses Oleh</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($records as $req)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 font-mono font-bold text-indigo-700">
                                    {{ $req->request_number }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $req->name }}</div>
                                    <div class="text-slate-500">{{ $req->department }} &bull; {{ $req->position }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-medium text-slate-800">{{ $req->type_label }}</span>
                                    @if($req->emergency)
                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-2xs font-bold bg-rose-50 text-rose-700 border border-rose-200">Darurat</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-700 font-medium">
                                    {{ optional($req->leave_date)->format('d M Y') ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    @if($req->type === 'half_day')
                                        {{ $req->duration ? $req->duration . ' Jam' : '4 Jam' }}
                                    @elseif($req->type === 'leave' || $req->type === 'sick')
                                        {{ $req->duration ? $req->duration . ' Hari' : '1 Hari' }}
                                    @elseif($req->type === 'emergency' || $req->type === 'personal')
                                        1 Hari
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-bold {{ $req->getStatusBadgeClasses() }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    @if($req->processor)
                                        <span class="font-medium text-slate-800">{{ $req->processor->name }}</span>
                                        <div class="text-2xs text-slate-500 uppercase">{{ $req->processor->role }}</div>
                                    @else
                                        <span class="text-slate-400 italic">Belum diproses</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('requests.show', $req->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 font-semibold transition">
                                        Detail &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $records->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center text-slate-500">
                <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-semibold text-slate-700">Tidak ada data pengajuan yang sesuai.</p>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan atau reset rentang tanggal filter Anda.</p>
            </div>
        @endif
    </div>
</div>
@endsection
