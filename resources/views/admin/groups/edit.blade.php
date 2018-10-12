@extends('layouts.layout')

@section('title')
    {{__('Edit Groups')}}
@endsection

@Section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@Section('content')
    <div class="container" id="group-edit">
        <h1>{{__('Edit Group')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    <group-edit ref="groupEdit" :input-data="{{$group}}" v-on:update="afterUpdate"></group-edit>
                    <footer class="modal-footer">
                        <div>
                            <b-button @click="onClose" class="btn btn-outline-success btn-sm text-uppercase">
                                CANCEL
                            </b-button>
                            <b-button @click="onSave" class="btn btn-success btn-sm text-uppercase">
                                SAVE
                            </b-button>
                        </div>
                    </footer>
                </div>
            </div>
            <div class="col-4">
                <div class="card card-body">
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore
                    et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                    aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                    cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                    culpa qui officia deserunt mollit anim id est laborum.
                </div>
            </div>
        </div>
    </div>
@endsection

@Section('js')
    <script src="{{mix('js/admin/groups/edit.js')}}"></script>
@endsection