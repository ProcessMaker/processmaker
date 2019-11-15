@extends('layouts.layout')

@section('title')
    {{__('Environment Variables')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Environment Variables') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="process-variables-listing">
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
                @can('create-environment_variables')
                    <button type="button" id="create_envvar" class="btn btn-secondary" @click="$refs.createEnvironmentVariable.show()">
                        <i class="fas fa-plus"></i> {{__('Environment Variable')}}
                    </button>
                @endcan
            </div>
        </div>
        <variables-listing ref="listVariable" :filter="filter"
                           :permission="{{ \Auth::user()->hasPermissionsFor('environment_variables') }}"
                           @delete="deleteVariable"></variables-listing>

        @can('create-environment_variables')
            <b-modal hidden 
                     ref="createEnvironmentVariable" 
                     title="{{__('Create Environment Variable')}}" 
                     ok-title="{{__('Save')}}"
                     @ok="onSubmit"
                     @hidden="onClose"
            >
            
                <div class="form-group">
                    {!!Form::label('name', __('Name'))!!}
                    {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'addEnvVariable.name',
                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addEnvVariable.errors.name}'])!!}
                    <small class="form-text text-muted"
                            v-if="!addEnvVariable.errors.name">{{ __('The environment variable name must be distinct.') }}</small>
                    <div class="invalid-feedback" v-for="name in addEnvVariable.errors.name">@{{name}}</div>
                </div>
                <div class="form-group">
                    {!!Form::label('description', __('Description'))!!}
                    {!!Form::textArea('description', null, ['class'=> 'form-control', 'v-model'=> 'addEnvVariable.description',
                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addEnvVariable.errors.description}','rows'=>3])!!}
                    <div class="invalid-feedback" v-for="description in addEnvVariable.errors.description">@{{description}}
                    </div>
                </div>
                <div class="form-group">
                    {!!Form::label('value', __('Value'))!!}
                    {!!Form::text('value', null, ['class'=> 'form-control', 'v-model'=> 'addEnvVariable.value',
                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addEnvVariable.errors.value}'])!!}
                    <div class="invalid-feedback" v-for="value in addEnvVariable.errors.value">@{{value}}</div>
                </div>
            </b-modal>
        @endcan
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/environment-variables/index.js')}}"></script>
@endsection
