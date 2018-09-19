@extends('layouts.layout', ['title' => 'Group Management'])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" v-cloak id="groups-listing">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">Groups</h1>
                        <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <button @click="showModal" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> {{__('Group')}}
                        </button>
                    </div>
                </div>
                <modal-group :show="groupModal" :labels="labels" @close="groupModal=false" :group-uid="groupUid"
                             v-on:reload="reload">
                </modal-group>
                <groups-listing ref="groupsListing" :filter="filter" v-on:edit="edit"></groups-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/management/groups/index.js')}}"></script>
@endsection
