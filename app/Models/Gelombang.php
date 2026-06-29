<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Gelombang extends Model
{
    protected $table = 'gelombang';

    protected $fillable = [
        'nama',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date'   => 'date:Y-m-d',
        ];
    }

    public static function findByDate(Carbon $date): ?self
    {
        return static::whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderBy('start_date')
            ->first();
    }

    public static function activeOrFirst(?Carbon $date = null): ?self
    {
        $date ??= Carbon::today();

        return static::findByDate($date)
            ?? static::orderBy('start_date')->first();
    }

    public function tahunAjaran(): int
    {
        return Carbon::parse($this->start_date)->year;
    }

    public function tahunPrefix(): string
    {
        return substr((string) $this->tahunAjaran(), -2);
    }
}
