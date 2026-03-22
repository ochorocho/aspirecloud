<?php

declare(strict_types=1);

use App\Http\Controllers\Web\ExtensionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('extensions.index'));

Route::get('/extensions', [ExtensionController::class, 'index'])->name('extensions.index');
Route::get('/extensions/{type}/{slug}', [ExtensionController::class, 'show'])->name('extensions.show');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        // Temporary redirect to the profile page until we have a dashboard to show
        return redirect()->route('profile.show');
    })->name('dashboard');
});

require __DIR__.'/inc/admin-web.php';
