<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PanelNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'panel_location_id',
        'external_location_id',
        'name',
        'uuid',
        'description',
        'data',
    ];

    protected $casts = [
        'data' => 'json'
    ];
}
