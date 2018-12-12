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
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab"
                           aria-controls="nav-home" aria-selected="true">Information</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-users" role="tab"
                           aria-controls="nav-profile" aria-selected="false">Users</a>
                    </div>
                </nav>


                <div class="card card-body tab-content mt-3" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                        {!! Form::open() !!}
                        <div class="form-group">
                            {!! Form::label('name', 'Name') !!}
                            {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                            <small class="form-text text-muted">Group name must be distinct</small>
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

                    <div class="tab-pane fade" id="nav-users" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <h1>{{__('Users in group')}}</h1>
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
                                <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#addUser">
                                    <i class="fas fa-plus"></i>
                                    {{__('User')}}</button>
                            </div>
                        </div>
                        <users-listing ref="listing" :filter="filter" v-on:reload="reload"></users-listing>
                    </div>
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
    <script src="{{mix('js/admin/groups/edit.js')}}"></script>
    <script>
//        import datatableMixin from "js/components/common/mixins/datatable";
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
                    ProcessMaker.apiClient.put('groups/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('{{__('Update Group Successfully')}}', 'success');
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