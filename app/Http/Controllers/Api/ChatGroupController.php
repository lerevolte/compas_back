<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Events\ChatGroupCreated;
use App\Models\ChatGroup;

class ChatGroupController extends Controller
{
    private ChatService $chatService;
 
    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function store(Request $request)
    {
        $group = ChatGroup::create(['name' => $request->name]);
        $users = collect($request->users);
        $users->push(auth()->user()->id);
        $group->users()->attach($users);

        broadcast(new ChatGroupCreated($group))->toOthers();
        
        return $group;
    }


    public function index(Request $request)
    {
        $data = $this->chatService->groups();

        return response()->json($data);
    }

    public function destroy($id)
    {
        $group = Article::find($id);

        if ($group) {
            broadcast(new ChatGroupDeleted($group))->toOthers();
            $group->delete();
        }

    }
}