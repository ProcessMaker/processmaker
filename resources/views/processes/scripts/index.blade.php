@extends('layouts.layout')
@section('title')
    {{__('Scripts')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="scriptIndex">
        <h1>{{__('Scripts')}}</h1>
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                        <i class="fas fa-search"></i>
                        </span>
                    </div>
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>

            <div class="col-8" align="right">
                <a href="#" class="btn btn-secondary" data-toggle="modal" data-target="#addScript"><i
                            class="fas fa-plus"></i>
                    {{__('Script')}}</a>
            </div>
        </div>
        <div class="container-fluid">
            <script-listing :filter="filter" ref="listScript" @delete="deleteScript"></script-listing>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="addScript">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Add A Script')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!!Form::label('title', __('Title'))!!}
                        {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'title', 'v-bind:class' =>
                        '{\'form-control\':true, \'is-invalid\':addError.title}'])!!}
                        <div class="invalid-feedback" v-for="title in addError.title">@{{title}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('description', __('Description'))!!}
                        {!!Form::textarea('description', null, ['rows'=>'2','class'=> 'form-control', 'v-model'=> 'description',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.description}'])!!}
                        <div class="invalid-feedback" v-for="description in addError.description">@{{description}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('language', __('Language'))!!}
                        {!!Form::select('language', [''=>__('Select'),'php' => 'PHP', 'lua' => 'Lua'], null, ['class'=>
                        'form-control', 'v-model'=> 'language', 'v-bind:class' => '{\'form-control\':true,
                        \'is-invalid\':addError.language}']);!!}
                        <div class="invalid-feedback" v-for="language in addError.language">@{{language}}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-dismiss="modal">{{__('Close')}}
                    </button>
                    <button type="button" class="btn btn-secondary" id="disabledForNow" @click="onSubmit">
                        {{__('Save')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/scripts/index.js')}}"></script>
    <script>
        new Vue({
            el: '#addScript',
            data: {
                title: '',
                language: '',
                description: '',
                code: '',
                addError: {},
            },
            methods: {
                onSubmit() {
                    this.errors = Object.assign({}, {
                        name: null,
                        description: null,
                        status: null
                    });
                    ProcessMaker.apiClient.post("/scripts", {
                        title: this.title,
                        language: this.language,
                        description: this.description,
                        code: "[]"
                    })
                    .then(response => {
                        ProcessMaker.alert('{{__('Script successfully added')}}', 'success');
                        window.location = "/processes/scripts/" + response.data.id + "/builder";
                    })
                    .catch(error => {
                        this.errors = error.response.data.errors;
                    })
                }
            }
        })
    </script>
@endsection