<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'division',
        'initials',
        'approved_by',
        'approved_at',
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
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin SCM' || str_contains(strtolower($this->role ?? ''), 'admin');
    }

    public function isGudang(): bool
    {
        return $this->role === 'Gudang' || str_contains(strtolower($this->role ?? ''), 'gudang');
    }

    public function isFinance(): bool
    {
        return in_array($this->role, ['Finance', 'Tim Pajak']) || str_contains(strtolower($this->role ?? ''), 'finance') || str_contains(strtolower($this->role ?? ''), 'pajak');
    }

    public function isScm(): bool
    {
        return in_array($this->role, ['SCM', 'Staf SCM']) || str_contains(strtolower($this->role ?? ''), 'scm');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
