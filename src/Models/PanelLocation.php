<?php

namespace BangerGames\ServerCreator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BangerGames\ServerCreator\Models\PanelLocation
 *
 * @property int $id
 * @property int $external_id
 * @property string|null $short_code
 * @property string|null $description
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PanelLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PanelLocation query()
 * @mixin \Eloquent
 */
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
