@extends('layouts.layout')

@section('title')
  {{__('Users')}}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container page-content" id="users-listing" v-cloak>
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">{{__('Users')}}</h1>
                <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <button type="button" href="#" class="btn btn-action text-white" data-toggle="modal" data-target="#create-user-modal"><i class="fas fa-plus"></i> {{__('User')}}</button>

                    <div class="modal fade" id="create-user-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1>Create New User</h1>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                
                                <div class="modal-body">
                                @include('admin.users.form')
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-success" data-dismiss="modal">Close</button>
                                    <button type="button" onclick="$('#userForm').submit()" class="btn btn-success ml-2">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <users-listing ref="listing" :filter="filter"></users-listing>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/admin/users/index.js')}}"></script>
@endsection
