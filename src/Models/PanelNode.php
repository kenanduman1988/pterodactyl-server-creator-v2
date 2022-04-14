<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BangerGames\ServerCreator\Models\PanelNode
 *
 * @property int $id
 * @property int $external_id
 * @property int $external_location_id
 * @property string $name
 * @property string $uuid
 * @property string|null $description
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $panel_location_id
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode query()
 * @mixin \Eloquent
 */
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
