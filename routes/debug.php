<?php

use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('/nightwatch-promethus/debug', function (NightwatchPromethusState $state) {
    return response()->json($state->snapshot());
})->name('nightwatch-promethus.debug');
