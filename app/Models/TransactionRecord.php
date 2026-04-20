<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRecord extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'reference_number',
        'user_id',
        'type',
        'category',
        'amount',
        'payment_method',
        'transaction_date',
        'notes',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_INCOME,
            self::TYPE_EXPENSE,
        ];
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
