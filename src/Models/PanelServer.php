<?php

namespace BangerGames\ServerCreator\Models;

use BangerGames\ServerCreator\Panel\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BangerGames\ServerCreator\Models\PanelServer
 *
 * @property int $id
 * @property int|null $server_id
 * @property string|null $uuid
 * @property string $status
 * @property int|null $suspended
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $steam_id
 * @property string|null $name
 * @property int|null $match_id
 * @property int|null $panel_node_id
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer query()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereMatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer wherePanelNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereSteamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereUuid($value)
 * @mixin \Eloquent
 * @property string|null $rcon_password
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServer whereRconPassword($value)
 */
class PanelServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'uuid',
        'status',
        'suspended',
        'steam_id',
        'match_id',
        'panel_node_id',
        'name',
        'data',
        'rcon_password',
    ];

    protected $casts = [
        'data' => 'json',
        'suspended' => 'boolean'
    ];
}
