<?php
// routes/api.php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('search')->group(function () {
    Route::post('/', [SearchController::class, 'search']);
    Route::post('/intelligent', [SearchController::class, 'intelligentSearch']);
    Route::get('/autocomplete', [SearchController::class, 'autocomplete']);
    Route::post('/fuzzy', [SearchController::class, 'fuzzySearch']);
    Route::get('/stats', [SearchController::class, 'searchStats']);
    Route::get('/document/{documentId}/search', [SearchController::class, 'searchWithinDocument']);
    Route::get('/document/{documentId}/similar', [SearchController::class, 'similarDocuments']);
});