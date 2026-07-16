<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'instructor_id', 'category_id', 'title', 'slug', 'description', 'content', 'requirements',
        'thumbnail', 'price', 'discount_price', 'level', 'status', 'reject_reason',
        'total_students', 'rating_avg', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'requirements' => 'array',
            'total_students' => 'integer',
            'rating_avg' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    protected function finalPrice(): Attribute
    {
        return Attribute::get(fn () => $this->discount_price ?? $this->price);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class)->withTimestamps();
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
