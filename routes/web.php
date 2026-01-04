<?php

use Illuminate\Support\Facades\Route;
use PhpJunior\Glosa\Http\Controllers\TranslationController;


Route::group([
    'prefix' => config('glosa.route_prefix', 'glosa'),
    'middleware' => config('glosa.middleware'),
], function () {
    Route::get('/', [TranslationController::class, 'index'])->name('glosa.index');

    Route::get('/translations/grouped', [TranslationController::class, 'grouped'])->name('glosa.api.translations.grouped');
    Route::get('/groups', [TranslationController::class, 'groups'])->name('glosa.api.groups');
    Route::put('/translations/value', [TranslationController::class, 'updateValue'])->name('glosa.api.translations.update-value');

    Route::post('/locales', [TranslationController::class, 'storeLocale'])->name('glosa.api.locales.store');
    Route::put('/locales/{id}', [TranslationController::class, 'updateLocale'])->name('glosa.api.locales.update');
    Route::delete('/locales/{id}', [TranslationController::class, 'destroyLocale'])->name('glosa.api.locales.destroy');
    Route::post('/keys', [TranslationController::class, 'storeKey'])->name('glosa.api.keys.store');
    Route::put('/keys/{id}', [TranslationController::class, 'updateKey'])->name('glosa.api.keys.update');
    Route::delete('/keys/{id}', [TranslationController::class, 'destroyKey'])->name('glosa.api.keys.destroy');
    Route::post('/import', [TranslationController::class, 'import'])->name('glosa.api.import');
});

if (config('glosa.enable_public_api')) {
    Route::get(config('glosa.public_api_url', 'api/translations/{locale}'), [TranslationController::class, 'publicTranslations'])->name('glosa.api.public.translations');
}
