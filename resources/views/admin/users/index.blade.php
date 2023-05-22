@extends('layouts.layout')

@section('title')
    {{__('Users')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Users') => null,
    ]])
@endsection
@section('content')
    <div class="px-3">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-item nav-link active" id="nav-users-tab" data-toggle="tab" href="#nav-users" role="tab"
                onclick="loadUsers()" aria-controls="nav-users" aria-selected="true">
                    {{ __('Users') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-deleted-users-tab" data-toggle="tab" href="#nav-deleted-users" role="tab"  
                onclick="loadDeletedUsers()" aria-controls="nav-deleted-users" aria-selected="true">
                    {{ __('Deleted Users') }}
                </a>
            </li>
        </ul>

        <div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="nav-users" role="tabpanel" aria-labelledby="nav-users-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('admin.users.list')
                    </div>
                </div>

                <div class="tab-pane fade show" id="nav-deleted-users" role="tabpanel" aria-labelledby="nav-deleted-users-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('admin.users.deletedUsers')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    loadUsers = function () {
        ProcessMaker.EventBus.$emit("api-data-users", true);
    };
    loadDeletedUsers = function () {
        ProcessMaker.EventBus.$emit("api-data-deleted-users", true);
    };
</script>

@section('js')
    <script src="{{mix('js/admin/users/index.js')}}"></script>
@endsection
