<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReminder extends Model
{
    protected $fillable = ['invoice_id', 'communication_message_id', 'channel', 'days_overdue', 'status', 'error_message'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function communicationMessage(): BelongsTo
    {
        return $this->belongsTo(CommunicationMessage::class);
    }
}
