<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PanelLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'short_code',
        'description',
        'data',
    ];

    protected $casts = [
        'data' => 'json'
    ];
}
