<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;

class Group extends Model
{

    use HasFactory, LogsActivity;

    protected $table = 'groups';

    protected $fillable = ['name', 'contact_number', 'address'];

    protected static $logAttributes = [
        'name', 'contact_number', 'address'
    ];

    protected static $logName = 'profile';

    protected static $logOnlyDirty = true;
    
    public function getDescriptionForEvent(string $eventName): string {
        return "Group was {$eventName}.";
    }

    public function clients() {
        return $this->hasMany(Client::class, 'group_id', 'id');
    }

    public function serviceTransactions() {
        return $this->hasMany(ServiceTransaction::class, 'group_id', 'id');
    }

}
