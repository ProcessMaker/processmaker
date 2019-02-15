@extends('layouts.layout', ['title' => __('Processes Management')])

@section('title')
{{__('Edit Process')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
@include('shared.breadcrumbs', ['routes' => [
    __('Processes') => route('processes.index'),
    __('Edit') . " " . $process->name => null,
]])
<div class="container" id="editProcess">
    <div class="row">
        <div class="col-8">
            <div class="card card-body">

                <div class="form-group">
                    {!!Form::label('processTitle', __('Process title'))!!}
                    {!!Form::text('processTitle', null,
                        [ 'id'=> 'name',
                            'class'=> 'form-control',
                            'v-model'=> 'formData.name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.processTitle">@{{errors.name[0]}}</div>
                </div>
                <div class="form-group">
                    {!! Form::label('description', __('Description')) !!}
                    {!! Form::textarea('description', null,
                        ['id' => 'description',
                            'rows' => 4,
                            'class'=> 'form-control',
                            'v-model' => 'formData.description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('category', __('Category')) !!}
                    {!! Form::select('category', $categories, null,
                        ['id' => 'process_category_id',
                            'class' => 'form-control',
                            'v-model' => 'formData.process_category_id',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.category}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.category">@{{errors.category[0]}}</div>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('startRequest', __('Start Request')) !!}
                    <multiselect
                        v-model="canStart"
                        :options="activeUsersAndGroups"
                        :multiple="true"
                        placeholder="Type to search"
                        track-by="fullname"
                        label="fullname"
                        group-values="items"
                        group-label="label">
                            <span slot="noResult">Oops! No elements found. Consider changing the search query.</span>
                    </multiselect>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('cancelRequest', __('Cancel Request')) !!}
                    <multiselect
                        v-model="canCancel"
                        :options="activeUsersAndGroups"
                        :multiple="true"
                        placeholder="Type to search"
                        track-by="fullname"
                        label="fullname"
                        group-values="items"
                        group-label="label">
                            <span slot="noResult">Oops! No elements found. Consider changing the search query.</span>
                    </multiselect>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('cancelScreen', __('Cancel Screen')) !!}
                    {!! Form::select('screens', $screens, null,
                        ['id' => 'screens',
                            'class' => 'form-control',
                            'v-model' => 'formData.cancel_screen_id',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.screens}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.screens">@{{errors.screens[0]}}</div>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('cancelRequest', __('Cancel Request')) !!}
                    <multiselect
                        v-model="canCancel"
                        :options="activeUsersAndGroups"
                        :multiple="true"
                        placeholder="Type to search"
                        track-by="fullname"
                        label="fullname"
                        group-values="items"
                        group-label="label">
                            <span slot="noResult">Oops! No elements found. Consider changing the search query.</span>
                    </multiselect>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('editData', __('Edit Data')) !!}
                    <multiselect
                        v-model="canEditData"
                        :options="activeUsersAndGroups"
                        :multiple="true"
                        placeholder="Type to search"
                        track-by="fullname"
                        label="fullname"
                        group-values="items"
                        group-label="label">
                            <span slot="noResult">Oops! No elements found. Consider changing the search query.</span>
                    </multiselect>
                </div>

                <div class="d-flex justify-content-end mt-2">
                    {!! Form::button('Cancel', ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                    {!! Form::button('Save', ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                </div>
            </div>

        </div>
        <div class="col-4">
            <div class="card card-body">
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia
                deserunt mollit anim id est laborum.
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script>
        test = new Vue({
            el: '#editProcess',
            data() {
                return {
                    formData: @json($process),
                    dataGroups: [],
                    value: [],
                    errors: {
                        name: null,
                        description: null,
                        category: null,
                        status: null,
                        screen: null
                    },
                    screens: @json($screens),
                    canStart: @json($canStart),
                    canCancel: @json($canCancel),
                    canEditData: @json($canEditData),
                    activeUsersAndGroups: @json($list)
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        description: null,
                        category: null,
                        status: null,
                        screen: null
                    });
                },
                onClose() {
                    window.location.href = '/processes';
                },
                formatAssigneePermissions(data) {
                    let response = {};

                    response['users'] = [];
                    response['groups'] = [];
                    
                    data.forEach(item => {
                        if (item.type == 'user') {
                            response['users'].push(parseInt(item.id));
                        }
                        
                        if (item.type == 'group') {
                            response['groups'].push(parseInt(item.id));
                        }
                    });
                    return response;
                },
                onUpdate() {
                    this.resetErrors();
                    let that = this;
                    this.formData.start_request = this.formatAssigneePermissions(this.canStart);
                    this.formData.cancel_request = this.formatAssigneePermissions(this.canCancel);
                    this.formData.edit_data = this.formatAssigneePermissions(this.canEditData);
                    this.formData.cancel_screen_id = this.formData.cancel_screen_id
                    ProcessMaker.apiClient.put('processes/' + that.formData.id, that.formData)
                        .then(response => {
                            ProcessMaker.alert('{{__('Process Updated Successfully')}}', 'success');
                            that.onClose();
                        })
                        .catch(error => {
                            //define how display errors
                            if (error.response.status && error.response.status === 422) {
                                // Validation error
                                that.errors = error.response.data.errors;
                            }
                        });
                }
            }
        });
    </script>
@endsection

@section('css')
    <style>
        .inline-input {
            margin-right: 6px;
        }
        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }
        .input-and-select {
            width: 212px;
        }
        .multiselect__tags-wrap {
            display: flex !important;
        }
        .multiselect__tag-icon:after {
            color: white !important;
        }
        .multiselect__option--highlight {
            background: #00bf9c !important;
        }
        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }
        .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }
        .multiselect__tag {
            background: #788793 !important;
        }
        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection