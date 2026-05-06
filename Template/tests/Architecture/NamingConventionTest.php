<?php

arch('Actions have Action suffix')
    ->expect('App\Application\Program\Actions')
    ->classes()
    ->toHaveSuffix('Action');

arch('Queries have Query suffix')
    ->expect('App\Application\Program\Queries')
    ->classes()
    ->toHaveSuffix('Query');
