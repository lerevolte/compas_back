<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Models\ChatGroup, App\Models\ChatMessage;

class ChatService
{
    public function groups(array $params = array())
    {
        $data = array();

        $items = ChatGroup::with('messages')->whereHas('users', function($q){
            $q->where('users.id', auth()->user()->id);
        })->get()->map(function($group) {
            $group->setRelation('messages', $group->messages->take(3));

            return $group;
        });

        foreach($items as $item) {
            $chat = array(
                'attached' => $item->attached,
                'is_active' => true,
                'photo' => '',
                'total_text_messages' => $item->messages->count(),
                'last_time_active' => $item->messages->count() ? $item->messages->last()->created_at : null,
                'messages' => array()
            );
            if($item->users->count() > 2) {
                $chat['first_name'] = $item->name;
                $chat['last_name'] = '';
                $chat['is_group_chat'] = true;
            } else {
                foreach($item->users as $user) {
                    if($user->id != auth()->user()->id) {
                        $chat['first_name'] = $user->name;
                        $chat['last_name'] = $user->last_name;
                        $chat['is_active'] = $user->deleted_at ? false : true;
                        $chat['is_group_chat'] = false;
                    }
                }
            }

            foreach($item->messages as $message) {
                if(isset($message->user->name))
                    $chat['messages'][] = array(
                        'id' => $message->id,
                        'user_id' => $message->user_id,
                        'first_name' => $message->user->name,
                        'last_name' => $message->user->last_name,
                        'message' => $message->message,
                        'files' => $message->files,
                        'images' => $message->images,
                        'audio_file' => $message->audio_file,
                        'read' => $message->read,
                        'created_at' => $message->created_at
                    );
            }

            $data[] = $chat;
        }

        return $data;
    }

    public function messages(array $params = array())
    {
        $items = array();

        if(!isset($params['group_id']))
            return [
                'error' => array(
                    'message' => 'group_id is empty',
                    'code' => 400
                )
            ];

        $group = ChatGroup::find($params['group_id']);
        if(!$group)
            return [
                'error' => array(
                    'message' => 'Chat not found',
                    'code' => 404
                )
            ];

        $limit = $params['per_page'] ?? 25;
        $page = $params['page'] ?? 1;

        $paginator = ChatMessage::orderBy('id', 'desc');

        if(isset($params['group_id']))
            $paginator = $paginator->where('group_id', $params['group_id']);

        $paginator = $paginator->paginate($limit);

        foreach ($paginator->items() as $item) {
            $items[] = array(
                'id' => $item->id,
                'user_id' => $item->user_id,
                'first_name' => $item->user->name,
                'last_name' => $item->user->last_name,
                'message' => $item->message,
                'files' => $item->files,
                'images' => $item->images,
                'audio_file' => $message->audio_file,
                'read' => $item->read ? true : false,
                'created_at' => $item->created_at
            );
        }

        $data = array(
            'count' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'data' => $items
        );

        return $data;
    }
}