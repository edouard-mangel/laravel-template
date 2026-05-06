<?php

arch('Domain has no Illuminate imports')
    ->expect('App\Domain')
    ->not->toUse('Illuminate');

arch('Domain has no Eloquent model references')
    ->expect('App\Domain')
    ->not->toUse('Illuminate\Database\Eloquent\Model');
