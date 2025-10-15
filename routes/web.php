<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ParserConfigurator;
use App\Livewire\FileProcessor;
use App\Livewire\TemplateManager;

Route::get('/', function () {
    return view('welcome');
});


Route::get('parser-configurator/{templateId?}', ParserConfigurator::class);

Route::get('process', FileProcessor::class);

Route::get('templates', TemplateManager::class);