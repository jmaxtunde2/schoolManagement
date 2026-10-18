<?php

namespace App\Services\WebPush;

class WebPushManager
{
    public function provider(): PushProvider
    {
        // Point d'extension unique : ajouter un `match` ici pour brancher un
        // autre relais push, sans toucher au reste de l'application.
        return app(PushProvider::class);
    }
}
