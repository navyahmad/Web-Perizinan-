<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'type',
        'leave_date',
        'start_time',
        'end_time',
        'half_day_type',
        'estimated_arrival',
        'duration',
        'reason',
        'emergency',
        'emergency_reason',
        'contactable',
        'status',
        'hrd_processed_by',
        'hrd_processed_at',
        'hrd_decision',
        'hrd_note',
        'processed_by',
        'processed_at',
        'approval_note',
        'rejection_reason',
        'email_status',
        'email_sent_at',
        'email_error',
        'telegram_status',
        'telegram_sent_at',
        'telegram_error',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'emergency' => 'boolean',
            'contactable' => 'boolean',
            'duration' => 'float',
            'hrd_processed_at' => 'datetime',
            'processed_at' => 'datetime',
            'email_sent_at' => 'datetime',
            'telegram_sent_at' => 'datetime',
        ];
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function hrdProcessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hrd_processed_by');
    }

    public function canBeProcessedBy(User $user): bool
    {
        return match ($this->status) {
            'pending' => $user->role === 'hrd',
            'pending_manager' => $user->role === 'admin' && $this->hrd_decision === 'approved',
            default => false,
        };
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeaveRequestAttachment::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'pending_manager'], true);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'late' => 'Izin Terlambat',
            'half_day' => 'Izin Setengah Hari',
            'early_departure' => 'Izin Pulang Lebih Awal',
            'temporary_exit' => 'Izin Keluar Kantor Sebentar',
            'leave' => 'Cuti',
            'emergency', 'personal' => 'Izin Darurat',
            'sick' => 'Izin Sakit',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu HRD',
            'pending_manager' => 'Menunggu Manager',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClasses(): string
    {
        return match ($this->status) {
            'pending', 'pending_manager' => 'bg-amber-500 text-white font-bold shadow-2xs',
            'approved' => 'bg-emerald-600 text-white font-bold shadow-2xs',
            'rejected' => 'bg-rose-600 text-white font-bold shadow-2xs',
            default => 'bg-slate-600 text-white font-bold',
        };
    }

    public function hasMedicalProof(): bool
    {
        return $this->attachments()->count() > 0;
    }
}
