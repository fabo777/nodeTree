<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NodeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/tree/{id?}', [NodeController::class, 'getTree']);
Route::post('/tree/{parentId}', [NodeController::class, 'addNode']);
Route::put('/tree/{id}', [NodeController::class, 'updateNode']);
Route::delete('/tree/{id}', [NodeController::class, 'deleteNode']);
Route::put('/tree/{id}/move/{newParentId}', [NodeController::class, 'moveNode']);
Route::put('/tree/{nodeId}/reorder', [NodeController::class, 'reorderNode']);
