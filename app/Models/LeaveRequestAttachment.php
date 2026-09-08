<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
