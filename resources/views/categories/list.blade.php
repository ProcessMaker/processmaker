@php
    $url = app('router')->getRoutes()->getByName($config->routes->editCategoryWeb)->uri;
    $editCatBaseUrl = '/' . explode('categories', $url)[0]  . 'categories';
@endphp
<div class="page-content mb-0" id="categories-listing">
    <div id="search-bar" class="search mb-3" vcloak>
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
                <div class="d-flex ml-md-2 flex-column flex-md-row">
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
        @edit="edit"
        count="{{$config->countField}}">
    </categories-listing>

    @can('create-process-categories')
        <div class="modal fade" tabindex="-1" role="dialog" id="createCategory" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ getTitle() }}</h5>
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

</div>

@section('js')
    <script>
      ProcessMaker.user.category = {};
      ProcessMaker.user.category.route = @json(route($config->routes->categoryListApi));
    </script>
    <script src="{{mix('js/processes/categories/index.js')}}"></script>
@append
