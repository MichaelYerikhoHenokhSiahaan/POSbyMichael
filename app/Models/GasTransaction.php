<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GasTransaction extends Model
{
    use HasFactory;

    public const TYPE_INPUT = 'input';

    public const TYPE_SALE = 'sale';

    protected $fillable = [
        'reference_number',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'unit_price',
        'total',
        'payment_method',
        'discount_applied',
        'stock_effect',
        'notes',
        'transaction_date',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_INPUT,
            self::TYPE_SALE,
        ];
    }

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_applied' => 'boolean',
            'transaction_date' => 'datetime',
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
