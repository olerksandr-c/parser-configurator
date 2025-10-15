<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ParserConfigurator;


Route::get('/', function () {
    return view('welcome');
});


Route::get('parser-configurator', ParserConfigurator::class);
