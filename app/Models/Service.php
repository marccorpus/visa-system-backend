<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    
    use HasFactory, SoftDeletes;

    protected $table = 'services';

    protected $fillable = ['parent_service_id', 'name', 'processing_type', 'processing_days', 'processing_minimum_days', 'processing_maximum_days'];

    public function parentService() {
      return $this->belongsTo(ParentService::class, 'parent_service_id', 'id');
    }

    public function rates() {
		  return $this->belongsToMany(Rate::class, 'rate_service', 'service_id', 'rate_id')->withPivot('cost', 'under', 'charge');
	  }

    public function documents() {
		  return $this->belongsToMany(Document::class, 'document_service', 'service_id', 'document_id')->withPivot('type');
	  }

    public function transactions() {
      return $this->belongsToMany(Transaction::class, 'service_transaction', 'service_id', 'transaction_id')->withPivot('client_id', 'group_id', 'cost', 'under', 'charge', 'status', 'created_at', 'created_by', 'updated_at', 'deleted_at', 'deleted_by', 'deletion_reason');
    }

    public function serviceTransactions() {
      return $this->hasMany(ServiceTransaction::class, 'service_id', 'id');
    }

}