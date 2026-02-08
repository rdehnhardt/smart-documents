<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\PublicDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/make-public', [DocumentController::class, 'makePublic'])->name('documents.make-public');
    Route::post('documents/{document}/make-private', [DocumentController::class, 'makePrivate'])->name('documents.make-private');
    Route::post('documents/{document}/apply-suggestions', [DocumentController::class, 'applySuggestions'])->name('documents.apply-suggestions');
    Route::post('documents/{document}/reanalyze', [DocumentController::class, 'reanalyze'])->name('documents.reanalyze');

    // Document sharing
    Route::post('documents/{document}/shares', [DocumentShareController::class, 'store'])->name('documents.shares.store');
    Route::patch('documents/{document}/shares/{user}', [DocumentShareController::class, 'update'])->name('documents.shares.update');
    Route::delete('documents/{document}/shares/{user}', [DocumentShareController::class, 'destroy'])->name('documents.shares.destroy');
});

Route::get('/p/{token}', [PublicDocumentController::class, 'show'])->name('public.document');
Route::get('/p/{token}/qr', [PublicDocumentController::class, 'qrCode'])->name('public.document.qr');

require __DIR__.'/settings.php';
