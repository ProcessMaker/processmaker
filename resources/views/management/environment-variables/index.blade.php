@extends('layouts.layout', ['title' => __('Environment Variables')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="variables-listing" v-cloak>
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">{{__('Environment Variables')}}</h1>
                <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <a @click="openAddVariableModal" href="#" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Variable')}}</a>
                </div>
            </div>
            <variables-listing ref="listing" :filter="filter"></variables-listing>
        </div>
    </div>
    <b-modal ref="addModal" size="md" @hidden="resetAddVariable" centered title="{{__('Create New Variable')}}" v-cloak>
        <b-alert dismissable :show="addVariableValidationError != null" variant="danger">@{{addVariableValidationError}}</b-alert>
        <form-input :error="addVariableValidationErrors.name" v-model="addVariable.name" label="{{__('Name')}}" helper="{{__('Name must be unique')}}"></form-input>
        <form-input :error="addVariableValidationErrors.description" v-model="addVariable.description" label="{{__('Description')}}"></form-input>
        <form-input type="password" :error="addVariableValidationErrors.value" v-model="addVariable.value" label="{{__('Value')}}"></form-input>

        <template slot="modal-footer">
        <b-button @click="hideAddModal" class="btn btn-outline-success btn-sm text-uppercase">
            Cancel
        </b-button>
        <b-button @click="submitAdd" class="btn btn-outline-success btn-sm text-uppercase">
            Save
        </b-button>
        </template>
    </b-modal>
</div>
@endsection

@section('js')
<script src="{{mix('js/management/environment-variables/index.js')}}"></script>
@endsection
