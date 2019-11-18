@php
    $url = app('router')->getRoutes()->getByName($config->routes->editCategoryWeb)->uri;
    $editCatBaseUrl = '/' . explode('categories', $url)[0]  . 'categories';
@endphp
<div class="px-3 page-content" id="categories-listing">
    <div id="search-bar" class="search mt-2 bg-light" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            @can('create-categories')
                <div class="d-flex ml-md-3 flex-column flex-md-row">
                    <button type="button" id="create_category" class="btn btn-secondary" data-toggle="modal"
                            data-target="#createCategory">
                        <i class="fas fa-plus"></i> {{ __('Category') }}
                    </button>
                </div>
            @endcan
        </div>
    </div>

    <categories-listing
        ref="list"
        @reload="reload"
        :filter="filter"
        :permission="{{ \Auth::user()->hasPermissionsFor('categories') }}"
        api-route="{{route($config->routes->categoryListApi)}}"
        load-on-start="{{$config->showCategoriesTab ?? true}}"
        location="{{$editCatBaseUrl}}"
        include="{{$config->apiListInclude}}"
        label-count="{{$config->labels->countColumn}}"
        count="{{$config->countField}}">
    </categories-listing>
</div>

@can('create-categories')
    <div class="modal" tabindex="-1" role="dialog" id="createCategory">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $config->labels->newCategoryTitle ?? __('Create Category')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!!Form::label('name', __('Category Name'))!!}
                        {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                        <small class="form-text text-muted" v-if="! errors.name">
                            {{ __('The category name must be distinct.') }}
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

@section('js')
    <script src="{{mix('js/processes/categories/index.js')}}"></script>

    @can('create-categories')
        <script>
          new Vue({
            el: "#createCategory",
            data: {
              errors: {},
              name: "",
              status: "ACTIVE",
              disabled: false,
              route: @json(route($config->routes->categoryListApi)),
              location: @json($editCatBaseUrl),
            },
            methods: {
              onClose () {
                this.name = "";
                this.status = "ACTIVE";
                this.errors = {};
              },
              onSubmit () {
                this.errors = {};
                //single click
                if (this.disabled) {
                  return;
                }
                this.disabled = true;
                ProcessMaker.apiClient.post(this.route, {
                  name: this.name,
                  status: this.status
                })
                  .then(response => {
                    ProcessMaker.alert('{{__('The category was created.')}}', "success");
                    window.location = this.location;
                  })
                  .catch(error => {
                    this.disabled = false;
                    if (error.response.status === 422) {
                      this.errors = error.response.data.errors;
                    }
                  });
              }
            }
          });
        </script>
    @endcan
@append
