<?php

use App\Http\Controllers\Admin\ContentManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('/admin')
    ->middleware(['auth', 'content.manage'])
    ->group(function () {
        Route::get('/content', [ContentManagementController::class, 'index'])->name('admin.content.index');
        Route::post('/content/draft', [ContentManagementController::class, 'saveDraft'])->name('admin.content.draft');
        Route::put('/content', [ContentManagementController::class, 'update'])->name('admin.content.update');
        Route::post('/content/rollback/{revision}', [ContentManagementController::class, 'rollback'])->name('admin.content.rollback');
        Route::post('/content/media', [ContentManagementController::class, 'uploadMedia'])->name('admin.content.media.upload');
        Route::delete('/content/media/{media}', [ContentManagementController::class, 'destroyMedia'])->name('admin.content.media.destroy');
    });
