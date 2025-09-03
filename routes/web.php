<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstagramDownloaderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Instagram Downloader Routes
Route::prefix('instagram')->name('instagram.')->group(function () {

    // Get media information
    Route::post('get-media', [InstagramDownloaderController::class, 'getMedia'])
        ->name('get.media');

    // Proxy thumbnail to avoid CORS
    Route::get('proxy/thumbnail', [InstagramDownloaderController::class, 'proxyThumbnail'])
        ->name('proxy.thumbnail');

    // Download media file
    Route::get('download', [InstagramDownloaderController::class, 'downloadMedia'])
        ->name('download');

    // Get stats
    Route::get('stats', [InstagramDownloaderController::class, 'getStats'])
        ->name('stats');
});

// Add CSRF token route for frontend
Route::get('csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});