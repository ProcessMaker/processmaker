@extends('layouts.layout')

@section('title')
    {{__('Edit Groups')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container" id="editGroup">
        <h1>{{__('Edit Group')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('name', 'Name') !!}
                        {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                        <small id="emailHelp" class="form-text text-muted">Group name must be distinct</small>
                        <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('description', 'Description') !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
                        <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('status', 'Status') !!}
                        {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null, ['id' => 'status', 'class' => 'form-control', 'v-model' => 'formData.status', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']) !!}
                        <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                        {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                    {!! Form::close() !!}
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

@section('js')
    <script>
        new Vue({
            el: '#editGroup',
            data() {
                return {
                    formData: @json($group),
                    errors: {
                        'name': null,
                        'description': null,
                        'status': null
                    }
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                    window.location.href = '/admin/groups';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('groups/' + this.formData.uuid, this.formData)
                        .then(response => {
                            ProcessMaker.alert('Update Group Successfully', 'success');
                            this.onClose();
                        })
                        .catch(error => {
                            //define how display errors
                            if (error.response.status && error.response.status === 422) {
                                // Validation error
                                this.errors = error.response.data.errors;
                            }
                        });
                }
            }
        });
    </script>
@endsection