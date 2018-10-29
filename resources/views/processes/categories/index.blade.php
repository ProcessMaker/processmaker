@extends('layouts.layout')

@section('title')
    {{__('Process Categories')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="process-categories-listing">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Process Categories')}}</h1>
                        <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <button type="button" class="btn btn-action text-light" data-toggle="modal"
                                data-target="#createProcessCategory">
                            <i class="fas fa-plus"></i> {{__('Category')}}
                        </button>
                    </div>
                </div>
                <categories-listing ref="list" @edit="editCategory" @delete="deleteCategory" :filter="filter">
                </categories-listing>
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="createProcessCategory">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Create New Process Category')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!!Form::label('name', __('Category Name'))!!}
                        {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                        <small class="form-text text-muted">{{ __('Category Name must be distinct') }}</small>
                        <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-dismiss="modal">{{__('Close')}}</button>
                    <button type="button" class="btn btn-secondary" @click="onSubmit"
                            id="disabledForNow">{{__('Save')}}</button>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/categories/index.js')}}"></script>
    <script>
        new Vue({
            el: '#createProcessCategory',
            data: {
                errors: {},
                name: '',
                status: 'ACTIVE',
            },
            methods: {
                onSubmit() {
                    this.errors = {};
                    let that = this;
                    ProcessMaker.apiClient.post('process_categories', {
                        name: this.name,
                        status: this.status
                    })
                        .then(response => {
                            ProcessMaker.alert('{{__('Category successfully added ')}}', 'success');
                            window.location = '/processes/categories';
                        })
                        .catch(error => {
                            if (error.response.status === 422) {
                                that.errors = error.response.data.errors
                            }
                        });
                }
            }
        })
    </script>
@endsection
