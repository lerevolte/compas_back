<?php

use Illuminate\Support\Facades\Broadcast;
use App\Broadcasting\ChatChannel;
use App\Models\ChatGroup;
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('tenant.{tenant}', function () {
    info('CHANNEL tenant');
    return true;//$tenant == tenant('id');
});
Broadcast::channel('object-updated', function ($user) {
    info('CHANNEL');
    info(tenant('id'));
    return true;
});
Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('groups.{group}', function ($user, ChatGroup $group) {
    return $group->hasUser($user->id);
});