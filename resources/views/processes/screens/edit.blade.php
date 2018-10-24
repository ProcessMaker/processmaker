@extends('layouts.layout')

@section('title')
    {{__('Edit Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container" id="screen-edit">
        <h1>{{__('Edit Screen')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    <screen-edit ref="screenEdit" :input-data="{{$screen}}" v-on:update="afterUpdate"></screen-edit>
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
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/screens/edit.js')}}"></script>
@endsection