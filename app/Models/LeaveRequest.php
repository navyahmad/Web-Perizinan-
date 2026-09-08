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
        'processed_by',
        'processed_at',
        'approval_note',
        'rejection_reason',
        'email_status',
        'email_sent_at',
        'email_error',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'emergency' => 'boolean',
            'contactable' => 'boolean',
            'duration' => 'float',
            'processed_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeaveRequestAttachment::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
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
            'leave' => 'Cuti',
            'personal' => 'Izin Pribadi',
            'sick' => 'Izin Sakit',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClasses(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700',
            'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700',
            default => 'bg-slate-100 text-slate-800 border-slate-300',
        };
    }

    public function hasMedicalProof(): bool
    {
        return $this->attachments()->count() > 0;
    }
}
