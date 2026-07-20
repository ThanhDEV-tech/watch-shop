<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const GENDER_TARGETS = ['men', 'women', 'unisex'];

    public const STATUSES = ['draft', 'active', 'inactive'];

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'gender_target',
        'description',
        'content',
        'thumbnail',
        'case_material',
        'strap_material',
        'glass_material',
        'water_resistance',
        'warranty_months',
        'warranty_note',
        'status',
        'rating_avg',
    ];

    protected function casts(): array
    {
        return [
            'brand_id' => 'integer',
            'category_id' => 'integer',
            'warranty_months' => 'integer',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function productImages(): HasMany
    {
        return $this->images();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'product_collection')
            ->withPivot('display_order')
            ->withTimestamps();
    }
}
