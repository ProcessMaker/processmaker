@extends('layouts.layout')

@section('title')
{{__('Process Categories')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container page-content" id="process-categories-listing">
  <h1>{{__('Process Categories')}}</h1>
  <div class="row">
    <div class="col">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">
            <i class="fas fa-search"></i>
          </span>
        </div>
        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
      </div>

    </div>
    <div class="col-8" align="right">
      <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#createProcessCategory">
        <i class="fas fa-plus"></i> {{__('Category')}}
      </button>
    </div>
  </div>
  <categories-listing ref="list" @edit="editCategory" @delete="deleteCategory" :filter="filter"></categories-listing>
</div>

<div class="modal" tabindex="-1" role="dialog" id="createProcessCategory">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{__('Create New Process Category')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          {!!Form::label('name', __('Category Name'))!!}
          {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
          <small class="form-text text-muted">{{ __('Category Name must be distinct') }}</small>
          <div class="invalid-feedback" v-for="name in errors.name">
            <blade @{{name}} </div> />
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{__('Close')}}</button>
          <button type="button" class="btn btn-secondary" @click="onSubmit" id="disabledForNow">{{__('Save')}}</button>
        </div>
      </div>

    </div>
    <div class="col-8" align="right">
      <a href="#" @click="showModal" class="btn btn-action" data-toggle="modal" data-target="#createGroup"><i class="fas fa-plus"></i>
      {{__('Category')}}</a>
    </div>
  </div>
  @endsection

  @section('js')
  <script src="{{mix('js/processes/categories/index.js')}}"></script>
  <script>
    new Vue({
      el: '#createProcessCategory',
      data: {
        errors: {},
        name: '',
        status: 'ACTIVE',
      },
      methods: {
        onSubmit() {
          this.errors = {};
          let that = this;
          ProcessMaker.apiClient.post('process_categories', {
              name: this.name,
              status: this.status
            })
            .then(response => {
              ProcessMaker.alert('{{__('
                Category successfully added ')}}', 'success');
              window.location = '/processes/categories';
            })
            .catch(error => {
              if (error.response.status === 422) {
                that.errors = error.response.data.errors
              }
            });
        }
      }
    })
  </script>
  @endsection
