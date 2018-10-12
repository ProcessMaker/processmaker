@extends('layouts.layout')

@section('title')
    {{__('Groups')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="groupIndex" v-cloak>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Groups')}}</h1>
                        <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <a href="#" @click="show" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Group')}}</a>
                    </div>
                </div>
                {{--<modal-create-group :show="groupModal" @close="groupModal=false" v-on:reload="reload">
                </modal-create-group>--}}
                <groups-listing ref="groupList" :filter="filter" v-on:reload="reload"></groups-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/index.js')}}"></script>
@endsection
