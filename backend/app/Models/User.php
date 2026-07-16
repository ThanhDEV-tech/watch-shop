<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'avatar',
        'phone',
        'bio',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->resolveAvatarUrl());
    }

    public function resolveAvatarUrl(?string $baseUrl = null): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
            return $this->avatar;
        }

        $storageUrl = Storage::disk('public')->url($this->avatar);

        if (! $baseUrl) {
            return Str::startsWith($storageUrl, ['http://', 'https://'])
                ? $storageUrl
                : url($storageUrl);
        }

        $storagePath = Str::startsWith($storageUrl, ['http://', 'https://'])
            ? (string) parse_url($storageUrl, PHP_URL_PATH)
            : $storageUrl;

        return rtrim($baseUrl, '/').'/'.ltrim($storagePath, '/');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function aiChatSessions(): HasMany
    {
        return $this->hasMany(AiChatSession::class);
    }

    public function instructorTotalCourses(): int
    {
        return $this->courses()->where('status', 'approved')->count();
    }

    public function instructorTotalStudents(): int
    {
        return Enrollment::query()
            ->whereIn('course_id', $this->courses()->where('status', 'approved')->select('id'))
            ->distinct('user_id')
            ->count('user_id');
    }

    public function instructorRatingAvg(): float
    {
        return round((float) $this->courses()->where('status', 'approved')->avg('rating_avg'), 1);
    }
}
