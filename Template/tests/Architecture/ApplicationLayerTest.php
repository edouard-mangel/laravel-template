<?php

arch('Application does not reference Infrastructure persistence')
    ->expect('App\Application')
    ->not->toUse('App\Infrastructure\Persistence');

arch('Application does not reference Http controllers')
    ->expect('App\Application')
    ->not->toUse('App\Http\Controllers');
