<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Contracts\Services\PhoneService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string|number $id
 * @property ?string $uuid
 * @property string $name
 * @property ?string $email
 * @property string $phone
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'last_name',
        'middle_name',
        'birthday',
        'accept_sms_notifications',
        'accept_sms_promotions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'accept_sms_notifications' => 'bool',
        'accept_sms_promotions' => 'bool',
    ];

    public function getPhoneAttribute(?string $value)
    {
        if (is_null($value)) {
            return '';
        }

        return resolve(PhoneService::class)->makeFormatted($value);
    }
}
