<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_group_user', 'user_id', 'group_id')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'group_id')->orderBy('id', 'desc');
    }
    
    public function hasUser($user_id)
    {
        foreach ($this->users as $user) {
            if($user->id == $user_id) {
                return true;
            }
        }
    }
}
