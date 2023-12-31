<?php

use App\Http\Controllers\Api\InfoController;
use App\Http\Controllers\Api\ReservationsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/room-types', [RoomTypeController::class, 'index']);
Route::get('/room-types-group', [RoomTypeController::class, 'getRoomType']);

//lan anh
Route::get('rooms/{slug}', [RoomController::class, 'show']);
Route::get('reservations/{adults}/{children}/{slug_rty}', [ReservationsController::class, 'index']);
//Tri
Route::get('/info', [InfoController::class, 'index']);

Route::get('room-type/{sty_id}', [RoomController::class, 'showRoomByRoomType']);