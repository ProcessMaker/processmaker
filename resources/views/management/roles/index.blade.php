@extends('layouts.layout', ['title' => 'Role Management'])

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
    <div class="container page-content" id="roles-listing">
        <!-- Role Add Dialog -->
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">Roles</h1>
                <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <a href="{{route('management-roles-add')}}" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Role')}}</a>
                </div>
            </div>
            <roles-listing :filter="filter"></roles-listing>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/management/roles/index.js')}}"></script>
@endsection
