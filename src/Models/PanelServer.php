<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PanelServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'uuid',
        'steam_id',
        'match_id',
        'panel_node_id',
        'name',
        'data',
        'rcon_password',
    ];

    protected $casts = [
        'data' => 'json',
    ];
}
