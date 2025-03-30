<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    /** @use HasFactory<\Database\Factories\ShortLinkFactory> */
    use HasFactory;

    protected $fillable = [
        "user_id",
        "title",
        "url",
        "short_url",
        "clicks",
        "qr_code",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function incrementClicks()
    {
        $this->increment("clicks");
    }
}
