<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{

    use HasFactory;

    protected $table = 'nationalities';

    public function clients() {
        return $this->hasMany(Client::class, 'nationality_id', 'id');
    }

}
