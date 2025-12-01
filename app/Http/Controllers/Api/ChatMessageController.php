<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Events\ChatMessageSent;
use App\Models\ChatMessage;

class ChatMessageController extends Controller
{
    private ChatService $chatService;
 
    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function store(Request $request)
    {
        $conversation = ChatMessage::create([
            'message' => $request->message,
            'group_id' => $request->group_id,
            'images' => $request->images,
            'files' => $request->files,
            'audio_file' => $request->audio_file,
            'user_id' => auth()->user()->id,
        ]);
        
        broadcast(new ChatMessageSent($conversation))->toOthers();
    
        return $conversation->load('user');
    }

    public function index(Request $request)
    {
        $data = $this->chatService->messages($request->all());

        return response()->json($data);
    }

    public function destroy($id)
    {
        $message = ChatMessage::find($id);

        if ($message && $message->user_id == auth()->user()->id) {
            broadcast(new ChatMessageDeleted($message))->toOthers();
            $message->delete();
        }

    }
}