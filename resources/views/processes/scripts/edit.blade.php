@extends('layouts.layout')

@section('title')
    {{__('Configure Script')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Scripts') => route('scripts.index'),
        __('Configure') . " " . $script->title => null,
    ]])
    <div class="container" id="editScript">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('title', 'Name') !!}
                        {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.title}']) !!}
                        <small class="form-text text-muted" v-if="! errors.title">{{ __('The script name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-if="errors.title">@{{errors.title[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('language', 'Language') !!}
                        {!! Form::select('language', $scriptFormats, 'null', ['id' => 'language','class'=> 'form-control', 'v-model' => 'formData.language',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.language}']) !!}
                        <div class="invalid-feedback" v-for="language in errors.language">@{{language}}</div>
                    </div>
                    <div class="form-group">
                        <label class="typo__label">{{__('Run script as')}}</label>
                        <multiselect v-model="selectedUser" label="fullname" :options="options"
                                     :searchable="true"></multiselect>
                    </div>


                    <div class="form-group">
                        {!! Form::label('description', 'Description') !!}
                        {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
                        'v-model' => 'formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.description}']) !!}
                        <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('timeout', 'Timeout') !!}
                        <div class="form-row ml-0">
                            {!! Form::text('timeout', null, ['id' => 'timeout', 'class'=> 'form-control col-1',
                            'v-model' => 'formData.timeout', 'pattern' => '[0-9]*', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.timeout}']) !!}                        
                            {!! Form::range(null, null, ['id' => 'timeout-range', 'class'=> 'custom-range col ml-1 mt-2',
                            'v-model' => 'formData.timeout', 'min' => 0, 'max' => 300]) !!}
                            <div class="invalid-feedback" v-if="errors.timeout">@{{errors.timeout[0]}}</div>
                        </div>
                        <small class="form-text text-muted" v-if="! errors.timeout">{{ __('How many seconds the script should be allowed to run (0 is unlimited).') }}</small>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button('Cancel', ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button('Save', ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        new Vue({
            el: '#editScript',
            data() {
                return {
                    formData: @json($script),
                    options:@json($users),
                    selectedUser:"",
                    errors: {
                        'title': null,
                        'language': null,
                        'description': null,
                        'timeout': null,
                        'status': null
                    }
                }
            },
            mounted() {
                let users = this.options.filter(u => {return u.id === this.formData.run_as_user_id});
                if (users.length > 0) {
                    this.selectedUser = users[0];
                }
            },
            methods: {
                resetErrors() {
                    this.errors = Object.assign({}, {
                        title: null,
                        language: null,
                        description: null,
                        status: null
                    });
                },
                onClose() {
                    window.location.href = '/processes/scripts';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('scripts/' + this.formData.id, {
                        title: this.formData.title,
                        language: this.formData.language,
                        description: this.formData.description,
                        run_as_user_id: this.selectedUser === null ? null : this.selectedUser.id,
                        timeout: this.formData.timeout,
                    })
                        .then(response => {
                            ProcessMaker.alert('The script was saved.', 'success');
                            this.onClose();
                        })
                        .catch(error => {
                            if (error.response.status && error.response.status === 422) {
                                if (error.response.data.errors.run_as_user_id !== undefined) {
                                    ProcessMaker.alert(error.response.data.errors.run_as_user_id[0], 'danger');
                                }
                                this.errors = error.response.data.errors;
                            }
                        });
                }
            }
        });
    </script>
@endsection
