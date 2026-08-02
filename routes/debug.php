<?php

use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Illuminate\Support\Facades\Route;

$middleware = config('nightwatch-promethus.routes.debug.middleware', ['web']);
$path = ltrim((string) config('nightwatch-promethus.routes.debug.path', 'nightwatch-promethus/debug'), '/');

Route::middleware($middleware)->get($path, function (NightwatchPromethusState $state) {
    return response()->json($state->snapshot());
})->name('nightwatch-promethus.debug');
