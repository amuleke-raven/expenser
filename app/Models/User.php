<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Impersonate, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country_id',
        'currency_id',
        'default_project_id',
        'department_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->can('access_admin_panel'),
            'staff' => true,
            'it' => $this->hasAnyRole(['it_staff', 'admin', 'super_admin']),
            default => false,
        };
    }

    public function canImpersonate(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function defaultProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'default_project_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_projects');
    }

    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'user_payment_methods')
            ->withPivot('is_preferred');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function initiatedRewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'initiated_by');
    }

    public function preferredPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethods()->wherePivot('is_preferred', true)->first()
               ?? $this->paymentMethods()->first();
    }

    public function toUsd(float $localAmount): float
    {
        $direction = config('remoteraven.conversion_rate_direction', 'per_base');
        $rate = (float) ($this->currency?->conversion_rate ?? 1);

        if ($direction === 'per_base') {
            return $rate > 0 ? $localAmount / $rate : $localAmount;
        }

        return $localAmount * $rate;
    }
}
