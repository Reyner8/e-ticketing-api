<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
   'comment_id',
   'user_id' 
])]

class CommentMention extends Model
{
    use HasActivityLog;
    
    // relations    
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }

    public function mentionedUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
