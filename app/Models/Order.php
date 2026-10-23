<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The order lifecycle. Each action says which statuses it can start from,
     * where it ends up, and which side of the deal is allowed to trigger it.
     */
    public const ACTIONS = [
        'confirm' => ['from' => [self::STATUS_PENDING], 'to' => self::STATUS_CONFIRMED, 'by' => ['seller']],
        'ship' => ['from' => [self::STATUS_CONFIRMED], 'to' => self::STATUS_SHIPPED, 'by' => ['seller']],
        'complete' => ['from' => [self::STATUS_SHIPPED], 'to' => self::STATUS_COMPLETED, 'by' => ['buyer']],
        'cancel' => ['from' => [self::STATUS_PENDING, self::STATUS_CONFIRMED], 'to' => self::STATUS_CANCELLED, 'by' => ['buyer', 'seller']],
    ];

    protected $fillable = [
        'reference', 'stone_id', 'buyer_id', 'seller_id', 'inquiry_id',
        'agreed_price', 'status', 'confirmed_at', 'shipped_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'agreed_price' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->reference ??= 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        });
    }

    public function stone(): BelongsTo
    {
        return $this->belongsTo(Stone::class)->withTrashed();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /**
     * Admins see everything, everyone else only the orders they are a party to.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return $query;
        }

        return $query->where(
            fn ($w) => $w->where('buyer_id', $user->id)->orWhere('seller_id', $user->id)
        );
    }

    public function partyFor(User $user): ?string
    {
        return match ($user->id) {
            $this->seller_id => 'seller',
            $this->buyer_id => 'buyer',
            default => null,
        };
    }

    /**
     * Actions the given user can take on this order right now.
     *
     * @return list<string>
     */
    public function actionsFor(User $user): array
    {
        $party = $this->partyFor($user);

        if ($party === null) {
            return [];
        }

        return array_keys(array_filter(
            self::ACTIONS,
            fn (array $rule) => in_array($this->status, $rule['from'], true) && in_array($party, $rule['by'], true)
        ));
    }
}
