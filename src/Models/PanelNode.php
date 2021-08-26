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
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode query()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereExternalLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PanelNode whereUuid($value)
 * @mixin \Eloquent
 */
class PanelNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
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
