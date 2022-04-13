<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
