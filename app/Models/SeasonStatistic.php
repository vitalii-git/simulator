<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeasonStatistic extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function calendar()
    {
        return $this->belongsTo(SeasonCalendar::class, 'calendar_id', 'id');
    }
}
