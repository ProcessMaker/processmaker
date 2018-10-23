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
                    <a href="#" @click="show" class="btn btn-action"><i class="fas fa-plus"></i> {{__('User')}}</a>
                </div>
            </div>
            <modal-create-user :show="userModal" :groups="{{$groups}}" @close="userModal=false" :user-id="userId"
                                  v-on:reload="reload"></modal-create-user>
            <users-listing ref="listing" :filter="filter" v-on:reload="reload"></users-listing>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/admin/users/index.js')}}"></script>
@endsection
