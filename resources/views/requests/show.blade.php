@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6"
     x-data="{
        approveModalOpen: false,
        rejectModalOpen: false,
        rejectionReason: '',
        approvalNote: '',
     }">

    <!-- Breadcrumbs -->
    <nav class="flex items-center text-xs text-slate-700 space-x-2">
        <a href="{{ route('requests.index') }}" class="hover:text-indigo-600 transition">&larr; Kembali ke Daftar Pengajuan</a>
        <span>/</span>
        <span class="font-mono text-slate-800 font-medium">{{ $leaveRequest->request_number }}</span>
    </nav>

    <!-- Medical Proof Warning Banner for Sick Leave without Proof -->
    @if($isSickWithoutMedicalProof)
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl shadow-2xs text-amber-900 flex items-start space-x-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
                <span class="font-bold block">Pemberitahuan Kebijakan Kantor (Izin Sakit Tanpa Bukti Medis):</span>
                    <span>Pengajuan sakit tanpa bukti medis dapat diperhitungkan sebagai izin pribadi sesuai kebijakan perusahaan. Keputusan berada pada HRD/Manager saat memproses approval.</span>
            </div>
        </div>
    @endif

    <!-- Main Detail Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Top Status Bar -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block">Nomor Pengajuan</span>
                <div class="flex items-center space-x-3 mt-1">
                    <span class="text-2xl font-mono font-extrabold text-slate-900">{{ $leaveRequest->request_number }}</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $leaveRequest->getStatusBadgeClasses() }}">
                        {{ $leaveRequest->status_label }}
                    </span>
                </div>
            </div>

            <!-- Approval/Rejection Actions or WhatsApp CTA -->
            <div class="flex flex-wrap items-center gap-2">
                @if($leaveRequest->canBeProcessedBy(auth()->user()))
                    <!-- Approve Button -->
                    <button type="button" @click="approveModalOpen = true"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-xs transition cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ auth()->user()->isAdmin() ? 'Setujui Final' : 'Setujui & Teruskan ke Manager' }}
                    </button>

                    <!-- Reject Button -->
                    <button type="button" @click="rejectModalOpen = true"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-xs transition cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Tolak (Reject)
                    </button>
                @else
                    <!-- WhatsApp CTA Button (wa.me) -->
                    @if($waUrl)
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-xs transition">
                            <!-- WhatsApp Icon -->
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            Kirim via WhatsApp
                        </a>
                    @endif
                @endif
            </div>
        </div>

        @if($leaveRequest->isPending())
            <div class="p-4 bg-amber-50 border-b border-slate-100 text-sm text-slate-700">
                {{ $leaveRequest->status === 'pending' ? 'Pengajuan harus ditinjau HRD terlebih dahulu.' : 'HRD sudah menyetujui. Pengajuan menunggu keputusan final Manager.' }}
            </div>
        @endif

        @if($leaveRequest->hrd_decision)
            <div class="p-4 bg-slate-50 border-b border-slate-100 text-sm text-slate-700 space-y-1">
                <p class="font-semibold">Keputusan HRD: {{ $leaveRequest->hrd_decision === 'approved' ? 'Disetujui HRD' : 'Ditolak HRD' }}</p>
                <p>{{ $leaveRequest->hrdProcessor?->name ?? 'Petugas' }} · {{ $leaveRequest->hrd_processed_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</p>
                @if($leaveRequest->hrd_note)
                    <p class="whitespace-pre-line">{{ $leaveRequest->hrd_note }}</p>
                @endif
            </div>
        @endif

        <!-- Decision Info Banner (if approved/rejected) -->
        @if(! $leaveRequest->isPending())
            <div class="p-4 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs text-slate-700 gap-2">
                <div class="flex items-center space-x-2">
                    <span class="font-semibold text-slate-700">Diproses oleh:</span>
                    <span class="font-bold text-slate-900">{{ $leaveRequest->processor->name ?? 'Petugas' }}</span>
                    <span class="px-2 py-0.5 rounded text-2xs font-bold uppercase {{ $leaveRequest->processor?->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $leaveRequest->processor ? ($leaveRequest->processor->isAdmin() ? 'MANAGER' : 'HRD') : '-' }}
                    </span>
                    <span>pada</span>
                    <strong>{{ optional($leaveRequest->processed_at)->translatedFormat('d F Y, H:i') }} WIB</strong>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-slate-700">Status Email:</span>
                    @if($leaveRequest->email_status === 'sent')
                        <span class="inline-flex items-center text-emerald-700 font-medium">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Terkirim
                        </span>
                    @elseif($leaveRequest->email_status === 'failed')
                        <span class="inline-flex items-center text-rose-600 font-medium" title="{{ $leaveRequest->email_error }}">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Gagal Kirim (Cek Log)
                        </span>
                    @else
                        <span class="text-slate-700 font-medium">Pending</span>
                    @endif
                </div>
            </div>
        @endif

        <div class="p-6 sm:p-8 space-y-8">
            <!-- SECTION: Data Pengaju (Snapshot) -->
            <div>
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                    Informasi Karyawan (Snapshot Pengajuan)
                </h3>
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-700 block">Nama Lengkap</span>
                        <span class="font-semibold text-slate-900">{{ $leaveRequest->name }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-700 block">Email Pribadi</span>
                        <span class="font-medium text-slate-900">{{ $leaveRequest->email }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-700 block">Nomor Telepon / WhatsApp</span>
                        <span class="font-mono text-slate-900 font-medium">{{ $leaveRequest->phone }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-700 block">Departemen</span>
                        <span class="font-semibold text-slate-900">{{ $leaveRequest->department }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-700 block">Jabatan / Posisi</span>
                        <span class="font-medium text-slate-900">{{ $leaveRequest->position }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-700 block">Waktu Submit</span>
                        <span class="font-medium text-slate-900">{{ $leaveRequest->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                    </div>
                </div>
            </div>

            <!-- SECTION: Rincian Izin -->
            <div>
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                    Rincian Izin
                </h3>
                <div class="border border-slate-200 rounded-xl p-5 space-y-4 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <span class="text-xs text-slate-700 block">Jenis Izin</span>
                            <span class="font-bold text-indigo-700 text-base">{{ $leaveRequest->type_label }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-700 block">Tanggal Izin</span>
                            <span class="font-semibold text-slate-900 text-base">
                                {{ \Carbon\Carbon::parse($leaveRequest->leave_date)->translatedFormat('d F Y') }}
                            </span>
                        </div>
                        @if($leaveRequest->duration)
                            <div>
                                <span class="text-xs text-slate-700 block">Durasi</span>
                                <span class="font-semibold text-slate-900">
                                    {{ $leaveRequest->duration }} {{ in_array($leaveRequest->type, ['half_day', 'temporary_exit'], true) ? 'Jam' : 'Hari' }}
                                </span>
                            </div>
                        @endif
                        @if(in_array($leaveRequest->type, ['half_day', 'temporary_exit'], true))
                            @if($leaveRequest->half_day_type)
                                <div>
                                    <span class="text-xs text-slate-700 block">Sub-Jenis Setengah Hari (Riwayat)</span>
                                    <span class="font-medium text-slate-900">{{ $leaveRequest->half_day_type }}</span>
                                </div>
                            @endif
                            <div>
                                <span class="text-xs text-slate-700 block">Jam Mulai & Selesai</span>
                                <span class="font-medium text-slate-900">{{ $leaveRequest->start_time }} - {{ $leaveRequest->end_time }} WIB</span>
                            </div>
                        @endif
                        @if($leaveRequest->type === 'early_departure')
                            <div>
                                <span class="text-xs text-slate-700 block">Jam Rencana Pulang</span>
                                <span class="font-medium text-slate-900">{{ $leaveRequest->start_time }} WIB</span>
                            </div>
                        @endif
                        @if($leaveRequest->type === 'late' && $leaveRequest->estimated_arrival)
                            <div>
                                <span class="text-xs text-slate-700 block">Estimasi Kedatangan</span>
                                <span class="font-medium text-slate-900">{{ $leaveRequest->estimated_arrival }} WIB</span>
                            </div>
                        @endif
                        @if($leaveRequest->type === 'sick' && $leaveRequest->contactable !== null)
                            <div>
                                <span class="text-xs text-slate-700 block">Dapat Dihubungi</span>
                                <span class="font-medium {{ $leaveRequest->contactable ? 'text-emerald-700' : 'text-slate-600' }}">
                                    {{ $leaveRequest->contactable ? 'Ya, dapat dihubungi' : 'Tidak' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Alasan Pengajuan -->
                    @if($leaveRequest->reason)
                        <div class="pt-3 border-t border-slate-100">
                            <span class="text-xs text-slate-700 block mb-1">Alasan Pengajuan:</span>
                            <p class="text-slate-800 bg-slate-50 p-3 rounded-lg border border-slate-200 whitespace-pre-line">
                                {{ $leaveRequest->reason }}
                            </p>
                        </div>
                    @endif

                    <!-- Emergency Reason -->
                    @if($leaveRequest->emergency)
                        <div class="pt-2">
                            <div class="bg-rose-50 border-l-4 border-rose-500 p-3 rounded-r text-xs text-rose-900">
                                <strong>Status Kondisi Darurat: YA</strong>
                                @if($leaveRequest->emergency_reason)
                                    <p class="mt-1"><strong>Alasan Darurat:</strong> {{ $leaveRequest->emergency_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECTION: Dokumen Pendukung -->
            <div>
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                    Dokumen / Lampiran Pendukung ({{ $leaveRequest->attachments->count() }})
                </h3>

                @if($leaveRequest->attachments->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($leaveRequest->attachments as $attachment)
                            <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 transition">
                                <div class="flex items-center space-x-3 truncate">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    </div>
                                    <div class="truncate">
                                        <span class="text-xs font-medium text-slate-900 block truncate">{{ $attachment->file_name }}</span>
                                        <span class="text-2xs text-slate-700">{{ $attachment->formatted_file_size }} &bull; {{ strtoupper(pathinfo($attachment->file_name, PATHINFO_EXTENSION)) }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('requests.attachment', ['id' => $leaveRequest->id, 'attachment_id' => $attachment->id]) }}"
                                    target="_blank"
                                    class="ml-2 inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-indigo-600 hover:bg-indigo-50 transition shrink-0">
                                    Buka File
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-dashed border-slate-300 text-center text-xs text-slate-700">
                        Tidak ada dokumen / bukti pendukung yang dilampirkan.
                    </div>
                @endif
            </div>

            <!-- SECTION: Catatan Approval / Alasan Rejection -->
            @if($leaveRequest->approval_note)
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm">
                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block mb-1">Catatan Persetujuan:</span>
                    <p class="text-emerald-950 font-medium">{{ $leaveRequest->approval_note }}</p>
                </div>
            @endif

            @if($leaveRequest->rejection_reason)
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm">
                    <span class="text-xs font-bold text-rose-800 uppercase tracking-wider block mb-1">Alasan Penolakan:</span>
                    <p class="text-rose-950 font-medium whitespace-pre-line">{{ $leaveRequest->rejection_reason }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: APPROVE CONFIRMATION -->
    @if($leaveRequest->canBeProcessedBy(auth()->user()))
    <div x-show="approveModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        
        <!-- Backdrop Overlay -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="approveModalOpen = false"></div>

        <!-- Center Dialog Container -->
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="relative z-10 w-full sm:max-w-lg transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all border border-slate-200 my-8"
                 @click.stop>
                <form method="POST" action="{{ route('requests.approve', $leaveRequest->id) }}">
                    @csrf
                    <div class="p-6 sm:p-8">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 text-center">
                            Setujui Pengajuan Izin Ini?
                        </h3>
                        <p class="text-xs text-slate-600 text-center mt-1">
                            Apakah Anda yakin ingin menyetujui pengajuan <strong>{{ $leaveRequest->request_number }}</strong> atas nama <strong>{{ $leaveRequest->name }}</strong>?
                        </p>

                        <p class="text-xs text-slate-600 text-center mt-2">
                            {{ auth()->user()->isAdmin() ? 'Persetujuan ini menjadi keputusan final dan mengirim email kepada karyawan.' : 'Persetujuan HRD akan diteruskan ke Manager. Izin belum disetujui final.' }}
                        </p>
                        <div class="mt-4">
                            <label for="approval_note" class="block text-xs font-semibold text-slate-700 mb-1">
                                Catatan Persetujuan (Opsional)
                            </label>
                            <textarea id="approval_note" name="approval_note" rows="2" maxlength="1000"
                                placeholder="Tambahkan catatan jika diperlukan..."
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-emerald-600"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                        <button type="button" @click="approveModalOpen = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-xs font-semibold text-white shadow-xs transition cursor-pointer">
                            {{ auth()->user()->isAdmin() ? 'Ya, Setujui Final' : 'Ya, Teruskan ke Manager' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: REJECT CONFIRMATION (REASON MANDATORY) -->
    <div x-show="rejectModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        
        <!-- Backdrop Overlay -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="rejectModalOpen = false"></div>

        <!-- Center Dialog Container -->
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="relative z-10 w-full sm:max-w-lg transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all border border-slate-200 my-8"
                 @click.stop>
                <form method="POST" action="{{ route('requests.reject', $leaveRequest->id) }}">
                    @csrf
                    <div class="p-6 sm:p-8">
                        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 text-center">
                            Tolak Pengajuan Izin
                        </h3>
                        <p class="text-xs text-slate-600 text-center mt-1">
                            Pengajuan <strong>{{ $leaveRequest->request_number }}</strong> akan ditolak. Alasan penolakan wajib dicatat.
                        </p>

                        <div class="mt-4">
                            <label for="rejection_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                                Alasan Penolakan <span class="text-rose-500">*</span>
                            </label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" required
                                placeholder="Tuliskan alasan penolakan secara jelas..."
                                class="w-full rounded-lg border border-rose-300 px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-rose-600 bg-rose-50/20"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                        <button type="button" @click="rejectModalOpen = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white shadow-xs transition cursor-pointer">
                            Tolak Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
