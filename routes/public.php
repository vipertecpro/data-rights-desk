<?php

use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Http\Controllers\ApiController;
use Vipertecpro\DataRightsDesk\Http\Controllers\NominationController;
use Vipertecpro\DataRightsDesk\Http\Controllers\NoticePageController;
use Vipertecpro\DataRightsDesk\Http\Controllers\PreferenceController;
use Vipertecpro\DataRightsDesk\Http\Controllers\RequestController;
use Vipertecpro\DataRightsDesk\Http\Controllers\RightsPageController;

/*
| Public pages: prefix and middleware from config('data-rights.routes.public').
*/

$purpose = '[a-z0-9_.-]{1,64}';

Route::get('/', RightsPageController::class)->name('index');
Route::get('notice', [NoticePageController::class, 'current'])->name('notice');
Route::get('notice/{version}', [NoticePageController::class, 'version'])->whereNumber('version')->name('notice.version');

Route::middleware('auth')->group(function () use ($purpose): void {
    Route::get('preferences', [PreferenceController::class, 'show'])->name('preferences');
    Route::post('consents/{purpose}', [PreferenceController::class, 'give'])->where('purpose', $purpose)->name('consents.give');
    Route::delete('consents/{purpose}', [PreferenceController::class, 'withdraw'])->where('purpose', $purpose)->name('consents.withdraw');
    Route::post('age', [PreferenceController::class, 'age'])->name('age');
    Route::post('guardian', [PreferenceController::class, 'guardian'])->name('guardian');

    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::post('requests', [RequestController::class, 'store'])->middleware('throttle:6,1')->name('requests.store');
    Route::get('requests/{request}', [RequestController::class, 'show'])->name('requests.show');
    Route::post('requests/{request}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');
    Route::get('requests/{request}/download', [RequestController::class, 'download'])->name('requests.download');

    Route::post('nominations', [NominationController::class, 'store'])->name('nominations.store');
    Route::delete('nominations/{nomination}', [NominationController::class, 'destroy'])->name('nominations.destroy');
});

if (config('data-rights.routes.api.enabled', true)) {
    Route::prefix('api')->name('api.')->group(function () use ($purpose): void {
        Route::get('notice', [ApiController::class, 'notice'])->name('notice');

        Route::middleware('auth')->group(function () use ($purpose): void {
            Route::get('purposes', [ApiController::class, 'purposes'])->name('purposes');
            Route::post('consents/{purpose}', [ApiController::class, 'give'])->where('purpose', $purpose)->name('consents.give');
            Route::delete('consents/{purpose}', [ApiController::class, 'withdraw'])->where('purpose', $purpose)->name('consents.withdraw');
            Route::get('requests', [ApiController::class, 'requests'])->name('requests.index');
            Route::post('requests', [ApiController::class, 'storeRequest'])->middleware('throttle:6,1')->name('requests.store');
            Route::get('requests/{request}', [ApiController::class, 'request'])->name('requests.show');
        });
    });
}
