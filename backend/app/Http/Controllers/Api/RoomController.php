<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('roomType', 'sale', 'packages', 'amenities', 'images')->orderBy('room_id', 'desc')->get();
        // Ẩn các trường 'rty_id' và 'sale_id'
        $rooms->makeHidden(['rty_id', 'sale_id']);
        return response()->json($rooms);
    }
    // Lan anh
    public function show($slug)
    {
        try {
            $room = Room::with('images', 'packages', 'amenities', 'roomType', 'sale')
            ->where('slug', $slug)
            ->firstOrFail();
            return response()->json(['room' => $room]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Room not found'], 404);
        }
    }
    //Tri
    public function showRoomByRoomType($sty_id)
    {
        try {
            $room = Room::with('images', 'packages', 'amenities', 'roomType', 'sale')
            ->where('rty_id', $sty_id)
            ->get();
            return response()->json(['room' => $room]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Room not found'], 404);
        }
    }
}
