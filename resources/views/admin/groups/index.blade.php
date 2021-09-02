@extends('layouts.layout')

@section('title')
    {{__('Groups')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Groups') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="listGroups">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <div id="search" class="mb-3 mb-md-0">
                        <div class="input-group w-100">
                            <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}" aria-label="{{__('Search')}}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                @can('create-groups')
                    <div class="d-flex ml-md-2 flex-column flex-md-row">
                        <button type="button" @click="$refs.createGroupModal.show()" class="btn btn-secondary" aria-label="{{ __('Create Group') }}" aria-haspopup="dialog">
                            <i class="fas fa-plus"></i> {{__('Group')}}
                        </button>
                        <pm-modal ref="createGroupModal" id="createGroupModal" title="{{__('Create Group')}}" @hidden="onClose" @ok.prevent="onSubmit" :ok-disabled="disabled" style="display: none;">
                            <div class="form-group">
                                {!! Form::label('name', __('Name')) !!}<small class="ml-1">*</small>
                                {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
                                'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                                <small id="emailHelp"
                                       class="form-text text-muted">{{__('Group name must be distinct')}}</small>
                                <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('description', __('Description')) !!}
                                {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=>
                                'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true,
                                \'is-invalid\':errors.description}']) !!}
                                <div class="invalid-feedback" v-for="description in errors.description">@{{description}}
                                </div>
                            </div>
                        </pm-modal>
                    </div>
                @endcan
            </div>
        </div>
        <div class="container-fluid">
            <groups-listing ref="groupList" :filter="filter"
                            :permission="{{ \Auth::user()->hasPermissionsFor('groups') }}"
                            v-on:reload="reload"></groups-listing>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/index.js')}}"></script>
    <script>
      new Vue({
        el: '#listGroups',
        data() {
          return {
            filter: '',
            formData: {},
            errors: {
              'name': null,
              'description': null,
              'status': null
            },
            disabled: false
          }
        },
        mounted() {
          this.resetFormData();
          this.resetErrors();
        },
        methods: {
          reload () {
            this.$refs.groupList.dataManager([
              {
                field: "updated_at",
                direction: "desc"
              }
            ]);
          },
          onClose() {
            this.resetFormData();
            this.resetErrors();
          },
          resetFormData() {
            this.formData = Object.assign({}, {
              name: null,
              description: null,
              status: 'ACTIVE'
            });
          },
          resetErrors() {
            this.errors = Object.assign({}, {
              name: null,
              description: null,
              status: null
            });
          },
          onSubmit() {
            this.resetErrors();
            //single click
            if (this.disabled) {
              return
            }
            this.disabled = true;
            ProcessMaker.apiClient.post('groups', this.formData)
              .then(response => {
                ProcessMaker.alert('{{__('The group was created.')}}', 'success');
                //redirect show group
                window.location = "/admin/groups/" + response.data.id + "/edit"
              })
              .catch(error => {
                //define how display errors
                if (error.response.status && error.response.status === 422) {
                  // Validation error
                  this.errors = error.response.data.errors;
                }
                this.disabled = false;
              });
          }
        }
      });
    </script>
@endsection
