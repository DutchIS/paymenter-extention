<?php

use Illuminate\Support\Facades\Route;

Route::post('/dutchis/power/{product}', [App\Extensions\Servers\DutchIS\DutchIS::class, 'power'])->name('extensions.dutchis.power');