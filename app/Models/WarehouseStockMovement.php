<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStockMovement extends Model
{
    use HasFactory;

    public const TYPE_ADD = 'add';

    public const TYPE_REMOVE = 'remove';

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'notes',
        'moved_at',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_ADD,
            self::TYPE_REMOVE,
        ];
    }

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
