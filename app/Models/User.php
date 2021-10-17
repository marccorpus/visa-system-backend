<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'contact_number', 'gender',
        'avatar_circle_color', 'avatar_top_type', 'avatar_top_color', 'avatar_hair_color', 'avatar_accessories_type', 'avatar_eyebrow_type', 'avatar_eye_type',
        'avatar_facial_hair_type', 'avatar_facial_hair_color', 'avatar_mouth_type', 'avatar_skin_color', 'avatar_clothes_type', 'avatar_clothes_color', 'avatar_graphic_type',
        'role_id'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime'];

    protected $appends = ['name'];

    public function role() {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function getNameAttribute()
    {
        return $this->attributes['first_name'] . ' ' . $this->attributes['last_name'];
    }

    public function sendPasswordResetNotification($token) {
        $url = config('app.frontend_url') . 'reset-password?token='.$token;

        $this->notify(new ResetPasswordNotification($url));
    }

    public function createdTransactions() {
        return $this->hasMany(Transaction::class, 'created_by', 'id');
    }

    public function deletedTransactions() {
        return $this->hasMany(Transaction::class, 'deleted_by', 'id');
    }

    public function createdPayments() {
        return $this->hasMany(Payment::class, 'created_by', 'id');
    }

    public function deletedPayments() {
        return $this->hasMany(Payment::class, 'deleted_by', 'id');
    }

    public function createdServiceTransactions() {
        return $this->hasMany(ServiceTransaction::class, 'created_by', 'id');
    }

    public function deletedServiceTransactions() {
        return $this->hasMany(ServiceTransaction::class, 'deleted_by', 'id');
    }

}
