@extends('layouts.app')

@section('content')
    @include('includes.sidebar')
    <main class="main-content position-relative max-height-vh-100 h-100">
        @include('includes.header')
        <div class="container-fluid py-4">
            <div class="row">
                @if (session('success'))
                    <div class="alert alert-primary alert-dismissible fade show" role="alert">
                        <span class="alert-icon"><i class="ni ni-like-2"></i></span>
                        <span class="alert-text"><strong>Success!</strong> {{ session('success') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <span class="alert-icon"><i class="ni ni-like-2"></i></span>
                        <span class="alert-text"><strong>Lỗi !</strong> {{ session('error') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

            </div>
            <div class="row">
                <div class="col-lg-7">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header pb-1">
                                    <div class="row">
                                        <div class="col-lg-4 col-4">
                                            <h5>Tiện ích phòng</h5>
                                        </div>
                                        <div class="col-lg-8 col-8 my-auto mb-2 d-flex text-end">
                                            <input class="form-control mx-3 mt" onkeyup="searchInTableAmenitiesFunction()"
                                                type="search" value="" placeholder="Nhập nội dung tìm kiếm..."
                                                id="search-input-amenities">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body px-0 pt-0 pb-2">
                                    <div class="table-responsive p-0">
                                        <table class="table align-items-center mb-0 px-3" id="table-amenities">
                                            <thead>
                                                <tr>
                                                    <th
                                                        class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                                        Tên</th>
                                                    <th class="text-secondary opacity-7"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($amenities as $item)
                                                    <tr>
                                                        <td>
                                                            <p class="text-xs font-weight-bold mb-0">{{ $item->name }}</p>
                                                        </td>
                                                        <td class="align-middle">
                                                            <div class="justify-content-end d-flex px-2 py-1">
                                                                <button
                                                                    class="btn btn-link text-info font-weight-bold text-xs mx-3 editAmenities"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#exampleModalEditAmenities"
                                                                    data-amenities_id="{{ $item->amenities_id }}">
                                                                    <i class="fas fa-pencil-alt text-default me-2"
                                                                        aria-hidden="true"></i>Edit
                                                                </button>
                                                                <button
                                                                    class="btn btn-link text-danger font-weight-bold text-xs mx-3 deleteAmenities"
                                                                    data-toggle="tooltip" data-bs-toggle="modal"
                                                                    data-bs-target="#exampleModalDeleteAmenities"
                                                                    data-amenities_id="{{ $item->amenities_id }}"><i
                                                                        class="far fa-trash-alt me-2"></i>
                                                                    Xóa
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="m-3" id="Pagination-amenities">
                                    {{ $amenities->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header pb-0">
                                    <h6>Thêm tiện ích</h6>
                                </div>
                                <div class="card-body px-0 pt-0 pb-2">
                                    <div class="table-responsive p-0">
                                        <form action="{{ route('amenities.create') }}" method="POST"
                                            class="needs-validation">
                                            @csrf
                                            <div class="form-group px-3">
                                                <label for="exampleFormControlInput1">Tên</label>
                                                <input type="text" class="form-control" name="name" id="name"
                                                    placeholder="Hướng biển" oninput="updateSlug()" required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid.
                                                </div>
                                            </div>
                                            <div class="form-group px-3">
                                                <label for="exampleFormControlInput1">Slug</label>
                                                <input type="text" class="form-control" name="slug" id="slug"
                                                    placeholder="huong-bien" required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid.
                                                </div>
                                            </div>
                                            <button type="submit" class=" btn bg-gradient-primary mx-3">Thêm</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal Edit --}}
            <div class="modal fade" id="exampleModalEditAmenities" tabindex="-1" role="dialog"
                aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Sửa
                                Loại phòng</h5>
                            <button type="button" class="btn-close text-dark" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="formEditAmenities">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="exampleFormControlInput1">Name</label>
                                    <input type="text" class="form-control" name="name" id="nameEdit">
                                </div>
                                <div class="form-group">
                                    <label for="exampleFormControlInput1">Slug</label>
                                    <input type="text" class="form-control" name="slug" id="slugEdit">
                                </div>
                                <button type="submit" class="btn bg-gradient-primary">Lưu</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Delete -->
            <div class="modal fade" id="exampleModalDeleteAmenities" tabindex="-1" role="dialog"
                aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                Delete </h5>
                            <button type="button" class="btn-close text-dark" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Bạn có muốn xóa tiện ích này không ?
                        </div>
                        <div class="modal-footer border-0">
                            <form method="POST" id="formDeleteRoomType">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn bg-gradient-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn bg-gradient-danger">Yes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @include('includes.footer')
    </main>
    <script>
        //sua nut phan trang sale
    var paginationamenities = document.getElementById("Pagination-amenities");
    var mypaginationamenities = paginationamenities.querySelectorAll("ul");
        mypaginationamenities.forEach(element => {

        element.classList.add("pagination-primary");
    });
    </script>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="../assets/js/soft-ui-dashboard.min.js?v=1.0.7"></script>
@endsection
