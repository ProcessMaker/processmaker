@extends('layouts.layout')

@section('title')
    {{__('Edit Process Category')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Categories') => route('categories.index'),
        __('Edit') . " " . $processCategory->name => null,
    ]])
    <div class="container" id="editProcessCategory">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {!! Form::label('name', __('Category Name')) !!}
                        {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.name}']) !!}
                        <small class="form-text text-muted" v-if="! errors.name">{{__('The category name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('status', __('Status')) !!}
                        {!! Form::select('status', ['ACTIVE' => __('active'), 'INACTIVE' => __('inactive')], null, ['id' => 'status',
                        'class' => 'form-control', 'v-model' => 'formData.status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
                        <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
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
                            ProcessMaker.alert('{{__('The category was saved.')}}', 'success');
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