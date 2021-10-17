<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{

    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'payments';

    protected $fillable = ['transaction_id', 'amount', 'note', 'created_by', 'deleted_by', 'deletion_reason'];

    protected static $logAttributes = ['transaction', 'amount', 'note', 'deletion_reason'];

    protected static $logName = 'payment';

    protected static $logOnlyDirty = true;

    protected static $recordEvents = ['created', 'deleted', 'restored'];

    public function getDescriptionForEvent(string $eventName): string {
        if($eventName == 'created' || $eventName == 'restored') {
            return "Payment was {$eventName}.";
        } elseif($eventName == 'deleted') {
            return "Payment was cancelled.";
        }
    }

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function deletedBy() {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

}
