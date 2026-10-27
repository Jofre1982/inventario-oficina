<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryDetail extends Model
{
    public $timestamps = false;

    protected $fillable = ['entry_id', 'item_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
