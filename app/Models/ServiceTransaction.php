<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTransaction extends Model
{
    
    use HasFactory, SoftDeletes;

    protected $table = 'service_transaction';

    protected $fillable = ['service_id', 'transaction_id', 'client_id', 'group_id', 'cost', 'under', 'charge', 'status', 'created_by', 'deleted_by', 'deletion_reason'];

    public function service() {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function client() {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function group() {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function deletedBy() {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

}
