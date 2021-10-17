<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentService extends Model
{

    use HasFactory;

    protected $table = 'document_service';

    protected $fillable = ['document_id', 'service_id', 'type'];

    public $timestamps = false;

}
