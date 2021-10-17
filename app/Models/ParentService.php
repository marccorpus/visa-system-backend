<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentService extends Model
{
    
    use HasFactory, SoftDeletes;

    protected $table = 'parent_services';

    protected $fillable = ['name'];

    public function services() {
        return $this->hasMany(Service::class, 'parent_service_id', 'id');
    }

}