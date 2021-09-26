<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
/**
 * Route to log mail recipient history
 */
Route::get('/mail-log/cdn/{id}', [Yuyu\MailLog\Http\Controllers\MailLogController::class, 'logMailVisitHistory'])->name('log-mail-visit-history');