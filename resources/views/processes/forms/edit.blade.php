@extends('layouts.layout')

@section('title')
    {{__('Edit Form')}}
@endsection

@Section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@Section('content')
    <div class="container" id="form-edit">
        <h1>{{__('Edit Form')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    <form-edit ref="formEdit" :input-data="{{$form}}" v-on:update="afterUpdate"></form-edit>
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

@Section('js')
    <script src="{{mix('js/processes/forms/edit.js')}}"></script>
@endsection