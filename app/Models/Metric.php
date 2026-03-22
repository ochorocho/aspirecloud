<?php

declare(strict_types=1);

namespace App\Models;

/**
 * @property-read int    $id
 * @property-read string $key
 * @property-read int    $value
 **/
class Metric extends BaseModel
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'integer',
    ];
}
