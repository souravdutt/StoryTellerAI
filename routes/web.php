<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/generate-text', [AIController::class, 'generateText'])->name('generate-text');
Route::post('/summarize-text', [AIController::class, 'summarizeText'])->name('summarize-text');
