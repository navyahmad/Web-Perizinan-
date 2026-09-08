<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Izin Disetujui</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 20px; background-color: #f8fafc; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: #059669; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 24px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        .table td.label { font-weight: 600; color: #64748b; width: 40%; }
        .footer { background: #f8fafc; padding: 16px 24px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-block; background: #059669; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Pengajuan Izin Disetujui</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $leaveRequest->name }}</strong>,</p>
            <p>Pengajuan izin kantor Anda telah <strong>DISETUJUI</strong> dengan rincian sebagai berikut:</p>

            <table class="table">
                <tr>
                    <td class="label">Nomor Pengajuan</td>
                    <td><strong>{{ $leaveRequest->request_number }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Jenis Izin</td>
                    <td>{{ $leaveRequest->type_label }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Izin</td>
                    <td>{{ \Carbon\Carbon::parse($leaveRequest->leave_date)->translatedFormat('d F Y') }}</td>
                </tr>
                @if($leaveRequest->type === 'half_day')
                    <tr>
                        <td class="label">Waktu Izin</td>
                        <td>{{ $leaveRequest->start_time }} - {{ $leaveRequest->end_time }} ({{ $leaveRequest->duration }} Jam)</td>
                    </tr>
                @elseif($leaveRequest->type === 'late')
                    <tr>
                        <td class="label">Estimasi Tiba</td>
                        <td>{{ $leaveRequest->estimated_arrival }} WIB</td>
                    </tr>
                @elseif($leaveRequest->duration)
                    <tr>
                        <td class="label">Durasi</td>
                        <td>{{ $leaveRequest->duration }} Hari</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Diproses Oleh</td>
                    <td>{{ strtoupper($leaveRequest->processor->role ?? 'Petugas') }} ({{ $leaveRequest->processor->name ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="label">Waktu Diproses</td>
                    <td>{{ optional($leaveRequest->processed_at)->translatedFormat('d F Y, H:i') }} WIB</td>
                </tr>
                @if($leaveRequest->approval_note)
                    <tr>
                        <td class="label">Catatan</td>
                        <td>{{ $leaveRequest->approval_note }}</td>
                    </tr>
                @endif
            </table>

            <p style="text-align: center;">
                <a href="{{ url('/cek-status') }}" class="btn">Cek Status Pengajuan</a>
            </p>

            <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
                Simpan nomor pengajuan ini untuk pengecekan status di kemudian hari.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Pesan ini dikirim secara otomatis oleh sistem.
        </div>
    </div>
</body>
</html>
