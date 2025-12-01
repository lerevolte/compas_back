<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Chat;
use App\Services\ChatService;
use App\Services\UserService;

class ChatController extends Controller
{
    private ChatService $chatService;
    private UserService $userService;
 
    public function __construct(ChatService $chatService, UserService $userService)
    {
        $this->chatService = $chatService;
        $this->userService = $userService;
    }

    public function list(Request $request)
    {
        $data = $this->chatService->get();

        return response()->json($data);
    }

    public function users(Request $request)
    {
        $data = $this->userService->get();

        return response()->json($data);
    }
}