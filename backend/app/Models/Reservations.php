<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use App\Models\Invoices;
use App\Models\Customer;
class Reservations extends Model
{
    protected $table = 'reservations'; // Định nghĩa tên bảng
    protected $primaryKey = 'reservations_id'; // Định nghĩa tên cột khóa chính
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasOne(Invoices::class, 'reservations_id');
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'reservation_room', 'reservations_id', 'room_id');
    }
    use HasFactory;
}
