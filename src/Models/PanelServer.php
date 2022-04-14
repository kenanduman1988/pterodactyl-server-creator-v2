<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BangerGames\ServerCreator\Models\PanelServer
 *
 * @property int $id
 * @property int|null $server_id
 * @property string|null $uuid
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $steam_id
 * @property string|null $name
 * @property int|null $match_id
 * @property int|null $panel_node_id
 * @property string|null $rcon_password
 * @property int|null $status_id
 * @property int|null $version
 * @property string|null $ip
 * @property int|null $port
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer query()
 * @mixin \Eloquent
 */
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
        'ip',
        'port',
    ];

    protected $casts = [
        'data' => 'json',
    ];
}
