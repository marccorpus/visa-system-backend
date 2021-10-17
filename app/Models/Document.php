<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'documents';

    protected $fillable = ['name'];

    public function services() {
		  return $this->belongsToMany(Service::class, 'document_service', 'document_id', 'service_id')->withPivot('type');
  	}

}
