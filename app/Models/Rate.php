<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rate extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'rates';

    protected $fillable = ['name'];

    public function services() {
      return $this->belongsToMany(Service::class, 'rate_service', 'rate_id', 'service_id')->withPivot('cost', 'under', 'charge');
    }

}
