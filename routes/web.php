<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ParserConfigurator;
use App\Livewire\FileProcessor;
use App\Livewire\TemplateManager;
use App\Livewire\ConsumptionProfileViewer;


use App\Livewire\CsvImporter;

Route::get('/', function () {
    return view('welcome');
});


Route::get('parser-configurator/{templateId?}', ParserConfigurator::class);

Route::get('process', FileProcessor::class);

Route::get('templates', TemplateManager::class);



Route::get('/import', CsvImporter::class)->name('csv.importer');


Route::get('/profiles', ConsumptionProfileViewer::class)->name('profiles.viewer');