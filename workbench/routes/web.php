<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\ExamplesController;

Route::redirect('/', '/malevich');

Route::middleware('web')->prefix('examples')->name('examples.')->group(function () {
    Route::get('/', [ExamplesController::class, 'overview'])->name('overview');
    Route::get('/projects', [ExamplesController::class, 'index'])->name('projects');
    Route::post('/projects', [ExamplesController::class, 'store'])->name('projects.store');
    Route::post('/projects/archive', [ExamplesController::class, 'archive'])->name('projects.archive');
    Route::get('/projects/export', [ExamplesController::class, 'export'])->name('projects.export');
    Route::get('/settings', [ExamplesController::class, 'settings'])->name('settings');
    Route::post('/settings', [ExamplesController::class, 'saveSettings'])->name('settings.save');
    Route::post('/tasks/{task}', [ExamplesController::class, 'task'])->name('tasks.update');
    Route::get('/assets/ui.css', function () {
        $path = dirname(__DIR__, 2).'/build/examples.css';
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'text/css']);
    })->name('css');
});
