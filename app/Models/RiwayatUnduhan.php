<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatUnduhan extends Model
{
    protected $table = 'job_statuses';

    protected $fillable = [
        'berkas_id',
        'download_status',
        'error_message',
        'attempted_at',
    ];

    public $timestamps = false;

    /**
     * Get the berkas that owns this download history.
     */
    public function berkas()
    {
        return $this->belongsTo(Berkas::class, 'berkas_id', 'id');
    }
}
