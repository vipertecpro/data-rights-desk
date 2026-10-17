<?php

use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Http\Controllers\ApiController;

/*
| JSON for apps with their own screens: prefix "{public prefix}/api", middleware and sign-in from
| config('data-rights.routes.api'). The defaults use the browser session; token clients (mobile
| apps, NativePHP) set middleware ['api'] and auth 'auth:sanctum'.
*/

$purpose = '[a-z0-9_.-]{1,64}';

Route::get('notice', [ApiController::class, 'notice'])->name('notice');

Route::middleware((array) config('data-rights.routes.api.auth', 'auth'))->group(function () use ($purpose): void {
    Route::get('purposes', [ApiController::class, 'purposes'])->name('purposes');
    Route::post('consents/{purpose}', [ApiController::class, 'give'])->where('purpose', $purpose)->middleware('throttle:30,1')->name('consents.give');
    Route::delete('consents/{purpose}', [ApiController::class, 'withdraw'])->where('purpose', $purpose)->middleware('throttle:30,1')->name('consents.withdraw');
    Route::get('requests', [ApiController::class, 'requests'])->name('requests.index');
    Route::post('requests', [ApiController::class, 'storeRequest'])->middleware('throttle:6,1')->name('requests.store');
    Route::get('requests/{request}', [ApiController::class, 'request'])->name('requests.show');
    Route::get('requests/{request}/download', [ApiController::class, 'download'])->name('requests.download');
});
