<?php

namespace App\Models;

use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = ['stone_id', 'buyer_id', 'message', 'offered_price', 'status', 'responded_at'];

    protected function casts(): array
    {
        return [
            'offered_price' => 'decimal:2',
            'responded_at' => 'datetime',
        ];
    }

    public function stone(): BelongsTo
    {
        return $this->belongsTo(Stone::class)->withTrashed();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /**
     * Sellers see inquiries on their stones, buyers see the ones they sent.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isSeller()
            ? $query->whereHas('stone', fn ($s) => $s->where('seller_id', $user->id))
            : $query->where('buyer_id', $user->id);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
