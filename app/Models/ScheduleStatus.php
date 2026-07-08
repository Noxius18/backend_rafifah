<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleStatus extends Model
{
    protected $table = 'schedule_statuses';

    protected $fillable = [
        'jadwal_id',
        'zoom_reminder_sent',
        'sent_at',
    ];

    protected $casts = [
        'zoom_reminder_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public $timestamps = false;

    public function jadwalTes()
    {
        return $this->belongsTo(JadwalTes::class, 'jadwal_id', 'id');
    }
}
