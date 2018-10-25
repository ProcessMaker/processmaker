@extends('layouts.layout')

@section('title')
    {{__('Edit Process Category')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container" id="editProcessCategory">
        <h1>{{__('Edit Process Category')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('name', 'Category Name') !!}
                        {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.name}']) !!}
                        <small class="form-text text-muted">{{ __('Category Name must be distinct') }}</small>
                        <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('status', 'Status') !!}
                        {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null, ['id' => 'status',
                        'class' => 'form-control', 'v-model' => 'formData.status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
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
        </div>
    </div>
@endsection

@section('js')
    <script>
        new Vue({
            el: '#editProcessCategory',
            data() {
                return {
                    formData: @json($processCategory),
                    errors: {
                        'name': null,
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
                    window.location.href = '/processes/categories';
                },
                onUpdate() {
                    this.resetErrors();
                    ProcessMaker.apiClient.put('process_categories/' + this.formData.id, this.formData)
                        .then(response => {
                            ProcessMaker.alert('Update Process Category Successfully', 'success');
                            this.onClose();
                        })
                        .catch(error => {
                            if (error.response.status && error.response.status === 422) {
                                this.errors = error.response.data.errors;
                            }
                        });
                }
            }
        });
    </script>
@endsection