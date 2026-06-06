<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('usuario.{id}', function ($user, $id) {
    return (int) $user->codigo === (int) $id;
});
