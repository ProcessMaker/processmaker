@extends('layouts.layout')

@section('title')
    {{ $title }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        $titleMenu => route($routeMenu),
        __('Categories') => null,
    ]])
@endsection
@section('content')
    <div class="px-3 page-content" id="categories-listing">
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
                @can($create)
                    <button type="button" id="create_category" class="btn btn-secondary" data-toggle="modal"
                            data-target="#createCategory">
                        <i class="fas fa-plus"></i> {{ $btnCreate }}
                    </button>
                @endcan
            </div>
        </div>
        <categories-listing ref="list" @edit="editCategory" @delete="deleteCategory" :filter="filter" api-route="{{$route}}" :permission="{{$permissions}}" location="{{$location}}" include="{{$include}}" label-count="{{$labelCount}}" count="{{$count}}">
        </categories-listing>
    </div>

    @can($create)
        <div class="modal" tabindex="-1" role="dialog" id="createCategory">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $titleModal }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('name', $fieldName)!!}
                            {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                            <small class="form-text text-muted" v-if="! errors.name">
                                {{ $distinctName }}
                            </small>
                            <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('status', __('Status')) !!}
                            {!! Form::select('status', ['ACTIVE' => __('active'), 'INACTIVE' => __('inactive')], null, ['id' => 'status',
                            'class' => 'form-control', 'v-model' => 'status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
                            <div class="invalid-feedback" v-for="status in errors.status">@{{status}}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="onSubmit" :disabled="disabled">
                            {{ __('Save') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endcan
@endsection

@section('js')
    <script>
      //Data needed for default search
      window.Processmaker.route = '{{ $route }}';
    </script>
    <script src="{{mix('js/processes/categories/index.js')}}"></script>

    @can($create)
        <script>
          new Vue({
            el: '#createCategory',
            data: {
              errors: {},
              name: '',
              status: 'ACTIVE',
              disabled: false,
              route: @json($route),
              location: @json($location),
            },
            methods: {
              onClose() {
                this.name = '';
                this.status = 'ACTIVE';
                this.errors = {};
              },
              onSubmit() {
                this.errors = {};
                //single click
                if (this.disabled) {
                  return
                }
                this.disabled = true;
                ProcessMaker.apiClient.post(this.route, {
                  name: this.name,
                  status: this.status
                })
                  .then(response => {
                    ProcessMaker.alert('{{__('The category was created.')}}', 'success', 5, true);
                    window.location = this.location;
                  })
                  .catch(error => {
                    this.disabled = false;
                    if (error.response.status === 422) {
                      this.errors = error.response.data.errors
                    }
                  });
              }
            }
          })
        </script>
    @endcan
@endsection
