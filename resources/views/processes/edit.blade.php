@extends('layouts.layout', ['title' => __('Processes Management')])

@section('title')
{{__('Edit Process')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
        <!doctype html>

<div class="container" id="editProcess">
    <h1>{{__('Edit Process')}}</h1>
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
                    {!! Form::label('summaryScreen', __('Summary screen')) !!}
                    {!! Form::select('summaryScreen', ['null' => '- No screen -'] + $screens, null,
                        ['id' => 'summary_screen_id',
                            'class' => 'form-control',
                            'v-model' => 'formData.summary_screen_id',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.screen}'
                        ])
                    !!}
                </div>
                <div class="form-group p-0">
                    {!! Form::label('startRequest', __('Start Request')) !!}
                    {!! Form::select('startRequest', $listStart, null, [
                            'id' => 'start_request_id',
                            'class' => 'form-control',
                            'v-model' => 'formData.start_request_id',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.screen}'
                        ])
                    !!}
                </div>
                <div class="form-group p-0">
                    {!! Form::label('cancelRequest', __('Cancel Request')) !!}
                    {!! Form::select('cancelRequest', $listCancel, null, [
                            'id' => 'cancel_request_id',
                            'class' => 'form-control',
                            'v-model' => 'formData.cancel_request_id',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.screen}'
                        ])
                    !!}
                </div>

                <div class="form-group p-0">
                    {!! Form::label('status', __('Status')) !!}
                    {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null,
                        ['id' => 'status',
                        'class' => 'form-control',
                        'v-model' => 'formData.status',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}'])
                    !!}
                    <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                    {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}
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
                    }
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

                    data.forEach(value => {
                        let option = value.split('-');
                        if (option[0] === 'user') {
                            response['users'].push(parseInt(option[1]));
                        }
                        if (option[0] === 'group') {
                            response['groups'].push(parseInt(option[1]));
                        }
                    });
                    return response;
                },
                onUpdate() {
                    this.resetErrors();
                    let that = this;
                    this.formData.start_request = this.formatAssigneePermissions([this.formData.start_request_id]);
                    this.formData.cancel_request = this.formatAssigneePermissions([this.formData.cancel_request_id]);

                    //if the summary screen id is not a number (e.g. null string)
                    // is set to null
                    this.formData.summary_screen_id =
                        isNaN(this.formData.summary_screen_id)
                            ? null
                            : this.formData.summary_screen_id;

                    ProcessMaker.apiClient.put('processes/' + that.formData.id, that.formData)
                        .then(response => {
                            ProcessMaker.alert('{{__('Update User Successfully')}}', 'success');
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