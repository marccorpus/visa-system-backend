<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = ['tracking', 'discount', 'created_by', 'deleted_by', 'deletion_reason'];

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function deletedBy() {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function payments() {
        return $this->hasMany(Payment::class, 'transaction_id', 'id');
    }

    public function services() {
        return $this->belongsToMany(Service::class, 'service_transaction', 'transaction_id', 'service_id')->withPivot('client_id', 'group_id', 'cost', 'under', 'charge', 'status', 'created_at', 'created_by', 'updated_at', 'deleted_at', 'deleted_by', 'deletion_reason');
    }

    public function serviceTransactions() {
        return $this->hasMany(ServiceTransaction::class, 'transaction_id', 'id');
    }

}
