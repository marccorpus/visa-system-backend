<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateService extends Model
{

    use HasFactory;

    protected $table = 'rate_service';

    protected $fillable = ['rate_id', 'service_id', 'cost', 'under', 'charge'];

    public $timestamps = false;

}
