<?php

arch('Actions have Action suffix')
    ->expect('App\Application\Product\Actions')
    ->classes()
    ->toHaveSuffix('Action');

arch('Queries have Query suffix')
    ->expect('App\Application\Product\Queries')
    ->classes()
    ->toHaveSuffix('Query');
