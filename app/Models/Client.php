<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;

class Client extends Model
{

    use HasFactory, LogsActivity;

    protected $table = 'clients';

    protected $fillable = [
        'group_id', 'first_name', 'last_name', 'passport_number', 'date_of_birth', 
        'nationality_id', 'gender', 'civil_status', 'contact_number', 'address'
    ];

    protected static $logAttributes = [
        'group', 'first_name', 'last_name', 'passport_number', 'date_of_birth', 'nationality',
        'gender', 'civil_status', 'contact_number', 'address'
    ];

    protected static $logName = 'profile';

    protected static $logOnlyDirty = true;
    
    public function getDescriptionForEvent(string $eventName): string {
        return "Client was {$eventName}.";
    }

    public function group() {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    public function nationality() {
        return $this->belongsTo(Nationality::class, 'nationality_id', 'id');
    }

    public function serviceTransactions() {
        return $this->hasMany(ServiceTransaction::class, 'client_id', 'id');
    }

}
