<?php

use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\ApiController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\BreachController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\CheckController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\ChildrenController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\ConsentController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\NoticeController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\OverviewController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\ProcessorController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\RequestController;
use Vipertecpro\DataRightsDesk\Http\Controllers\Desk\RetentionController;

/*
| Dashboard: prefix and middleware from config('data-rights.routes.dashboard'),
| plus the viewDataRightsDesk gate on every route.
*/

Route::get('/', OverviewController::class)->name('index');

Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
Route::get('requests/create', [RequestController::class, 'create'])->name('requests.create');
Route::post('requests', [RequestController::class, 'store'])->name('requests.store');
Route::get('requests/{request}', [RequestController::class, 'show'])->name('requests.show');
Route::post('requests/{request}/verify', [RequestController::class, 'verify'])->name('requests.verify');
Route::post('requests/{request}/status', [RequestController::class, 'status'])->name('requests.status');
Route::post('requests/{request}/complete', [RequestController::class, 'complete'])->name('requests.complete');
Route::post('requests/{request}/reject', [RequestController::class, 'reject'])->name('requests.reject');
Route::post('requests/{request}/erase', [RequestController::class, 'erase'])->name('requests.erase');
Route::get('requests/{request}/summary', [RequestController::class, 'summary'])->name('requests.summary');

Route::get('consents', [ConsentController::class, 'index'])->name('consents.index');
Route::post('consents/proof', [ConsentController::class, 'proof'])->name('consents.proof');

Route::get('notices', [NoticeController::class, 'index'])->name('notices.index');
Route::post('notices/publish', [NoticeController::class, 'publish'])->name('notices.publish');
Route::get('notices/{notice}', [NoticeController::class, 'show'])->name('notices.show');

Route::get('breaches', [BreachController::class, 'index'])->name('breaches.index');
Route::get('breaches/create', [BreachController::class, 'create'])->name('breaches.create');
Route::post('breaches', [BreachController::class, 'store'])->name('breaches.store');
Route::get('breaches/{breach}', [BreachController::class, 'show'])->name('breaches.show');
Route::put('breaches/{breach}', [BreachController::class, 'update'])->name('breaches.update');
Route::post('breaches/{breach}/affected', [BreachController::class, 'affected'])->name('breaches.affected');
Route::post('breaches/{breach}/board-alerted', [BreachController::class, 'boardAlerted'])->name('breaches.board-alerted');
Route::post('breaches/{breach}/board-reported', [BreachController::class, 'boardReported'])->name('breaches.board-reported');
Route::post('breaches/{breach}/notify', [BreachController::class, 'notify'])->name('breaches.notify');
Route::get('breaches/{breach}/draft/{kind}', [BreachController::class, 'draft'])->whereIn('kind', ['alert', 'report', 'person'])->name('breaches.draft');

Route::get('retention', [RetentionController::class, 'index'])->name('retention.index');
Route::post('retention/holds', [RetentionController::class, 'storeHold'])->name('retention.holds.store');
Route::post('retention/holds/{hold}/release', [RetentionController::class, 'releaseHold'])->name('retention.holds.release');

Route::get('processors', [ProcessorController::class, 'index'])->name('processors.index');
Route::get('processors/create', [ProcessorController::class, 'create'])->name('processors.create');
Route::post('processors', [ProcessorController::class, 'store'])->name('processors.store');
Route::get('processors/{processor}/edit', [ProcessorController::class, 'edit'])->name('processors.edit');
Route::put('processors/{processor}', [ProcessorController::class, 'update'])->name('processors.update');

Route::get('children', ChildrenController::class)->name('children');
Route::get('check', CheckController::class)->name('check');

Route::prefix('api')->name('api.')->group(function (): void {
    Route::get('summary', [ApiController::class, 'summary'])->name('summary');
    Route::get('requests', [ApiController::class, 'requests'])->name('requests');
    Route::get('requests/{request}', [ApiController::class, 'request'])->name('requests.show');
    Route::get('consents', [ApiController::class, 'consents'])->name('consents');
    Route::get('breaches', [ApiController::class, 'breaches'])->name('breaches');
    Route::get('breaches/{breach}', [ApiController::class, 'breach'])->name('breaches.show');
    Route::get('notices', [ApiController::class, 'notices'])->name('notices');
    Route::get('retention', [ApiController::class, 'retention'])->name('retention');
    Route::get('check', [ApiController::class, 'check'])->name('check');
});
