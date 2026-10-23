<?php

namespace App\Models;

use Database\Factories\StoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Stone extends Model
{
    /** @use HasFactory<StoneFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_SOLD = 'sold';

    public const SHAPES = ['Round', 'Princess', 'Cushion', 'Emerald', 'Oval', 'Pear', 'Marquise', 'Radiant', 'Heart'];
    public const COLORS = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
    public const CLARITIES = ['FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2', 'SI1', 'SI2'];
    public const CUTS = ['Ideal', 'Excellent', 'Very Good', 'Good'];
    public const LABS = ['GIA', 'IGI', 'HRD'];

    protected $fillable = [
        'sku',
        'shape',
        'carat',
        'color',
        'clarity',
        'cut',
        'lab',
        'certificate_no',
        'price_per_carat',
        'status',
        'image_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'carat' => 'decimal:2',
            'price_per_carat' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Stone $stone) {
            $stone->sku ??= 'GL-'.strtoupper(Str::random(8));
        });

        // Keep the stored total in step with carat and rate so it can be filtered in SQL.
        static::saving(function (Stone $stone) {
            $stone->total_price = round($stone->carat * $stone->price_per_carat, 2);
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public const SORTS = [
        'newest' => ['created_at', 'desc'],
        'price_asc' => ['total_price', 'asc'],
        'price_desc' => ['total_price', 'desc'],
        'carat_asc' => ['carat', 'asc'],
        'carat_desc' => ['carat', 'desc'],
    ];

    /**
     * Apply the catalogue filters. Unknown or empty values are ignored.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $multi = fn (string $key, array $allowed) => array_values(
            array_intersect((array) ($filters[$key] ?? []), $allowed)
        );

        return $query
            ->when($multi('shape', self::SHAPES), fn ($q, $v) => $q->whereIn('shape', $v))
            ->when($multi('color', self::COLORS), fn ($q, $v) => $q->whereIn('color', $v))
            ->when($multi('clarity', self::CLARITIES), fn ($q, $v) => $q->whereIn('clarity', $v))
            ->when($multi('lab', self::LABS), fn ($q, $v) => $q->whereIn('lab', $v))
            ->when(is_numeric($filters['carat_min'] ?? null), fn ($q) => $q->where('carat', '>=', $filters['carat_min']))
            ->when(is_numeric($filters['carat_max'] ?? null), fn ($q) => $q->where('carat', '<=', $filters['carat_max']))
            ->when(is_numeric($filters['price_min'] ?? null), fn ($q) => $q->where('total_price', '>=', $filters['price_min']))
            ->when(is_numeric($filters['price_max'] ?? null), fn ($q) => $q->where('total_price', '<=', $filters['price_max']));
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['newest'];

        return $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
