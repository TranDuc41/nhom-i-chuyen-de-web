<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Image;
use App\Models\Room;
use App\Models\Sale;
use App\Models\RoomType;
use App\Models\Amenities;
use Illuminate\Support\Facades\File;
class RoomController extends Controller
{
    public function index()
    {
        // Lấy danh sách phòng với thông tin giảm giá
        $rooms = DB::table('room')
            ->select('room.*', 'sale.discount as discount_percentage', 'room_type.name as room_type_name')
            ->leftJoin('sale', 'room.sale_id', '=', 'sale.sale_id')
            ->leftJoin('room_type', 'room.rty_id', '=', 'room_type.rty_id')
            ->orderBy('room_id', 'desc')
            ->paginate(10);

        $totalRoom = DB::table('room')->count();
        $totalRoomMaintenance = DB::table('room')->where('status', 'maintenance')->count();
        $totalRoomUsed = DB::table('room')->where('status', 'used')->count();
        $totalRoomType = DB::table('room_type')->count();

        return view('rooms', compact('rooms', 'totalRoom', 'totalRoomType', 'totalRoomMaintenance', 'totalRoomUsed'));
    }

    public function create()
    {
        $currentDate = Carbon::now();

        $roomTypes = DB::table('room_type')->get();
        $sales = DB::table('sale')->where('end_date', '>=', $currentDate)->get();
        $packages = DB::table('packages')->get();
        $amenities = DB::table('amenities')->get();
        return view('editRoom', compact('roomTypes', 'sales', 'packages', 'amenities'));
    }

    public function store(Request $request)
    {
        try {
            // Xác định các giá trị từ request
            $title = trim($request->input('room-name'));
            $price = trim($request->input('room-price'));
            $adults = trim($request->input('room-adults'));
            $children = trim($request->input('room-children'));
            $area = trim($request->input('room-area'));
            $description = $request->input('description-input');
            $rty_id = $request->input('kind-room');
            $sale_id = $request->input('sale-select');
            $packageIds = $request->input('package-room');
            $amenitieIds = $request->input('room-amenities');
            $inputStatus = $request->input('room-status');

            // Kiểm tra package và amenities
            if (!$this->validateIds('packages', $packageIds) || !$this->validateIds('amenities', $amenitieIds)) {
                return redirect()->back()->with('error', 'Giá trị trong tiện nghi hoặc gói lưu trú không hợp lệ!')->withInput();
            }

            // Kiểm tra số từ trong mô tả
            if ($this->validateWordCount($description, 5000)) {
                return redirect()->back()->with('error', 'Nội dung không được vượt quá 5000 từ!')->withInput();
            }

            // Kiểm tra rty_id và sale_id
            if (!$this->validateId('room_type', 'rty_id', $rty_id) || (!$sale_id == 0 && !$this->validateId('sale', 'sale_id', $sale_id))) {
                return redirect()->back()->with('error', 'Giá trị trong giảm giá và loại phòng không hợp lệ!')->withInput();
            }

            // Kiểm tra và xử lý giá trị trước khi lưu vào cơ sở dữ liệu
            if ($this->validateRoomData($title, $price, $adults, $children, $area, $inputStatus, $description)) {
                // Tạo đối tượng Room
                $room = new Room([
                    'title' => $title,
                    'slug' => $this->createUniqueSlug($title) . '-' . uniqid(),
                    'price' => $price,
                    'adults' => $adults,
                    'children' => $children,
                    'area' => $area,
                    'rty_id' => $rty_id,
                    'sale_id' => ($sale_id == 0) ? null : $sale_id,
                    'description' => $description,
                    'status' => $inputStatus,
                ]);

                // Lưu phòng để có được ID
                $room->save();

                // Kiểm tra và xử lý ảnh
                $this->processImages($room, $request->file('images'));

                // Lưu vào bảng room_package và room_amenities
                $this->saveRoomPackageAndAmenities($room, $packageIds, $amenitieIds);

                return redirect()->route('rooms.index')->with('success', 'Thêm phòng thành công.');
            } else {
                return redirect()->back()->with('error', 'Vui lòng điền đầy đủ thông tin hoặc kiểm tra giá trị nhập vào.')->withInput();
            }
        } catch (\Throwable $th) {
            // dd($th);
            return redirect()->back()->with('error', 'Thêm thất bại! Vui lòng kiểm tra lại dữ liệu nhập vào.')->withInput();
        }
    }

    // Hàm kiểm tra và xử lý ảnh
    private function processImages($room, $images)
    {
        if (!empty($images)) {
            foreach ($images as $image) {
                // Kiểm tra xem có phải là file ảnh hay không
                if ($image->isValid() && $this->isImage($image)) {
                    $imageName = 'dominion' . '_' . $image->getClientOriginalName();
                    // Kiểm tra xem tên ảnh đã tồn tại trong bảng image hay chưa
                    if (!$this->isImageNameExists($imageName)) {
                        $image->move(public_path('uploads'), $imageName);
                    } else {
                        $imageName = 'dominion' . '_' . uniqid() . '_' . $imageName;
                        $image->move(public_path('uploads'), $imageName);
                    }

                    // Lưu thông tin ảnh vào bảng image và liên kết với phòng thông qua mối quan hệ đa hình
                    $imageModel = new Image([
                        'name' => $imageName,
                        'img_src' => '/uploads/' . $imageName,
                    ]);

                    $room->images()->save($imageModel);
                }
            }
        }
    }

    // Hàm lưu vào bảng room_package và room_amenities
    private function saveRoomPackageAndAmenities($room, $packageIds, $amenitieIds)
    {
        $roomPackageData = [];
        foreach ($packageIds as $packageId) {
            $roomPackageData[] = [
                'room_id' => $room->room_id,
                'packages_id' => $packageId,
            ];
        }

        $amenitiesData = [];
        foreach ($amenitieIds as $amenitieId) {
            $amenitiesData[] = [
                'room_id' => $room->room_id,
                'amenities_id' => $amenitieId,
            ];
        }

        DB::table('room_package')->insert($roomPackageData);
        DB::table('room_amenities')->insert($amenitiesData);
    }

    // Các hàm kiểm tra
    private function validateIds($table, $ids)
    {
        $existingIds = DB::table($table)->whereIn($table . '_id', $ids)->pluck($table . '_id');
        $nonExistingIds = array_diff($ids, $existingIds->toArray());
        return empty($nonExistingIds);
    }

    private function validateId($table, $column, $id)
    {
        return DB::table($table)->where($column, $id)->exists();
    }

    private function validateWordCount($text, $limit)
    {
        return str_word_count($text) > $limit;
    }

    private function validateRoomData($title, $price, $adults, $children, $area, $status, $description)
    {
        return $title && $price > 0 && $price < 1000000000 && $adults > 0 && $adults < 30 &&
            $children >= 0 && $children < 6 && $area > 0  && !empty(trim($description)) &&
            in_array($status, ['work', 'maintenance', 'used']);
    }


    //Kiểm tra tên ảnh đã tồn tại trong bảng image hay chưa
    private function isImageNameExists($imageName)
    {
        return Image::where('name', $imageName)->exists();
    }


    public function show($id)
    {
    }

    public function edit($slug)
    {
        $room = Room::where('slug', $slug)->first();
        if (!$room) {
            return redirect()->back()->with('error', 'Phòng không tồn tại.');
        }
        $images = $room->images()->paginate(4);

        $currentDate = Carbon::now();
        $sales = Sale::where('end_date', '>=', $currentDate)->get();
        $packages = $packages = DB::table('packages')->get();
        $roomTypes = RoomType::all();
        $amenities = Amenities::all();

        return view('editRoom', compact('room', 'images', 'sales', 'packages', 'roomTypes', 'amenities'));
    }

    public function update(Request $request, $id)
    {
        try {
            // Lấy giá trị từ request
            $title = trim($request->input('room-name'));
            $price = trim($request->input('room-price'));
            $slug = trim($request->input('room-slug'));
            $adults = trim($request->input('room-adults'));
            $children = trim($request->input('room-children'));
            $area = trim($request->input('room-area'));
            $description = $request->input('description-input');
            $rty_id = $request->input('kind-room');
            $sale_id = $request->input('sale-select');
            $packageIds = $request->input('package-room');
            $amenitieIds = $request->input('room-amenities');
            $inputStatus = $request->input('room-status');

            $room = Room::where('slug', $slug)->firstOrFail(); // Lấy ra phòng cần cập nhật

            // Kiểm tra package và amenities
            if (!$this->validateIds('packages', $packageIds) || !$this->validateIds('amenities', $amenitieIds)) {
                return redirect()->back()->with('error', 'Giá trị không hợp lệ!');
            }

            // Kiểm tra số từ trong mô tả
            if ($this->validateWordCount($description, 5000)) {
                return redirect()->back()->with('error', 'Nội dung không được vượt quá 5000 từ!');
            }

            // Kiểm tra rty_id và sale_id
            if (!$this->validateId('room_type', 'rty_id', $rty_id) || (!$sale_id == 0 && !$this->validateId('sale', 'sale_id', $sale_id))) {
                return redirect()->back()->with('error', 'Giá trị không hợp lệ!');
            }

            // Kiểm tra và xử lý giá trị trước khi lưu vào cơ sở dữ liệu
            if ($this->validateRoomData($title, $price, $adults, $children, $area, $inputStatus, $description)) {
                // Cập nhật thông tin phòng
                $room->title = $title;
                $room->slug = $this->createUniqueSlug($title) . '-' . uniqid();
                $room->price = $price;
                $room->adults = $adults;
                $room->children = $children;
                $room->area = $area;
                $room->rty_id = $rty_id;
                $room->sale_id = ($sale_id == 0) ? null : $sale_id;
                $room->description = $description;
                $room->status = $inputStatus;

                // Lưu các thay đổi
                $room->save();

                // Xóa hình ảnh cũ trước khi thêm hình ảnh mới
                // $room->images()->delete();

                // Kiểm tra và xử lý ảnh
                $this->processImages($room, $request->file('images'));

                // Xóa các package của room trước đó
                DB::table('room_package')
                    ->where('room_id', $room->room_id)
                    ->delete();

                // Xóa các amenities của room trước đó
                DB::table('room_amenities')
                    ->where('room_id', $room->room_id)
                    ->delete();
                
                // Lưu vào bảng room_package và room_amenities
                $this->saveRoomPackageAndAmenities($room, $packageIds, $amenitieIds);
                
                return redirect()->route('rooms.index')->with('success', 'Cập nhật phòng thành công.');
            } else {
                return redirect()->back()->with('error', 'Vui lòng điền đầy đủ thông tin hoặc kiểm tra giá trị nhập vào.');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Cập nhật thất bại! Vui lòng kiểm tra lại dữ liệu nhập vào.');
        }
    }

    public function destroy($slug)
    {
        // Lấy thông tin của room từ cơ sở dữ liệu
        $room = DB::table('room')->where('slug', $slug)->first();
        $images = DB::table('image')->where('imageable_id', $room->room_id)->get();

        if (!$room) {
            session()->flash('error', 'Không tìm thấy phòng.');
            return response()->json(['message' => 'Xóa thất bại.']);
        }

        foreach ($images as $image) {
            // Xóa hình ảnh từ thư mục uploads
            $filePath = public_path($image->img_src);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
        // Xóa room từ cơ sở dữ liệu
        DB::table('room')->where('slug', $slug)->delete();
        DB::table('image')->where('imageable_id', $room->room_id)->delete();

        // Xóa các package của room trước đó
        DB::table('room_package')
            ->where('room_id', $room->room_id)
            ->delete();

        // Xóa các amenities của room trước đó
        DB::table('room_amenities')
            ->where('room_id', $room->room_id)
            ->delete();

        session()->flash('success', 'Xóa thành công.');
        return response()->json(['message' => 'Xóa thành công.']);
    }

    //Tạo slug
    private function createUniqueSlug($title)
    {
        $slug = Str::slug($title);

        // Kiểm tra xem có bản ghi nào trong cơ sở dữ liệu có slug giống nhau không
        while (DB::table('room')->where('slug', $slug)->exists()) {
            // Nếu có, thêm một số duy nhất vào slug để tạo slug mới và duy nhất
            $slug = Str::slug($title) . '-' . uniqid();
        }

        return $slug;
    }

    //Kiểm tra file ảnh
    public function isImage($file)
    {
        // Kiểm tra xem tệp có phải là hình ảnh hay không
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
            $extension = $file->getClientOriginalExtension();

            return in_array(strtolower($extension), $allowedExtensions);
        }

        return false;
    }
}
