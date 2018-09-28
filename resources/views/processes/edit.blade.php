@extends('layouts.layout', ['title' => __('Processes Management')])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<!doctype html>

<div class="container">
    <h1>Edit Process</h1>
    <div class="row">
        <div class="col-8">
            <div class="card card-body">
                
                <div class="form-group">
                    <label for="processTitle">Title</label>
                    <input type="text" class="form-control" id="processTitle" value="{{$process->name}}">
                    
                </div>
                <div class="form-group">
                    <label for="processTitle">Description</label>
                    <textarea class="form-control" rows="3" id="processDescription"></textarea>
                </div>
                <div class="form-group p-0">
                    <label for="dropdownSelect">Category</label>
                    <select class="form-control" id="dropdownSelect">
                        <option>No Category</option>
                        <option>Category</option>
                        <option>Heyo</option>
                    </select>
                </div>
                <div class="form-group p-0">
                    <label for="dropdownSelect">Status</label>
                    <select class="form-control" id="dropdownSelect">
                        <option>Active</option>
                        <option>Inactive</option>
                        <option>Draft</option>
                    </select>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="btn btn-outline-success">Close</button>
                    <button type="button" class="btn btn-success ml-2">Save</button>
                </div>
            </div>

        </div>
        <div class="col-4">
            <div class="card card-body">
            Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>
@endsection