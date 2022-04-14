<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BangerGames\ServerCreator\Models\PanelServerActivity
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $panel_server_id
 * @property int|null $action_id
 * @property int|null $status_id
 * @property array|null $data
 * @property-read \App\Model\PanelServer $server
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServerActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServerActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelServerActivity query()
 * @mixin \Eloquent
 */
class PanelServerActivity extends Model
{
    protected $fillable = [
        'panel_server_id',
        'action_id',
        'status_id',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];

    /**
     * @return BelongsTo
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(PanelServer::class,'panel_server_id','id');
    }
}
