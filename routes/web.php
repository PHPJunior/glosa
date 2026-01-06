<?php

use Illuminate\Support\Facades\Route;
use PhpJunior\Glosa\Http\Controllers\TranslationController;
use PhpJunior\Glosa\Http\Controllers\LocaleController;
use PhpJunior\Glosa\Http\Controllers\TranslationKeyController;
use PhpJunior\Glosa\Http\Controllers\TranslationValueController;
use PhpJunior\Glosa\Http\Controllers\ImportExportController;
use PhpJunior\Glosa\Http\Controllers\PublicTranslationController;

Route::group([
    'prefix' => config('glosa.route_prefix', 'glosa'),
    'middleware' => config('glosa.middleware'),
], function () {
    Route::get('/', [TranslationController::class, 'index'])->name('glosa.index');
    Route::get('/translations/grouped', [TranslationController::class, 'grouped'])->name('glosa.api.translations.grouped');

    Route::get('/groups', [TranslationKeyController::class, 'groups'])->name('glosa.api.groups');
    Route::put('/translations/value', [TranslationValueController::class, 'update'])->name('glosa.api.translations.update-value');

    Route::post('/locales', [LocaleController::class, 'store'])->name('glosa.api.locales.store');
    Route::put('/locales/{id}', [LocaleController::class, 'update'])->name('glosa.api.locales.update');
    Route::delete('/locales/{id}', [LocaleController::class, 'destroy'])->name('glosa.api.locales.destroy');

    Route::post('/keys', [TranslationKeyController::class, 'store'])->name('glosa.api.keys.store');
    Route::put('/keys/{id}', [TranslationKeyController::class, 'update'])->name('glosa.api.keys.update');
    Route::delete('/keys/{id}', [TranslationKeyController::class, 'destroy'])->name('glosa.api.keys.destroy');

    Route::post('/import', [ImportExportController::class, 'import'])->name('glosa.api.import');
    Route::get('/export', [ImportExportController::class, 'export'])->name('glosa.api.export');
});

if (config('glosa.enable_public_api')) {
    Route::get(config('glosa.public_api_url', 'api/translations/{locale}'), PublicTranslationController::class)->name('glosa.api.public.translations');
}
