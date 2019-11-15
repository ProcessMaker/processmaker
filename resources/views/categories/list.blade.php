@php
    $url = app('router')->getRoutes()->getByName($config->routes->editCategoryWeb)->uri;
    $editCatBaseUrl = '/' . explode('categories', $url)[0]  . 'categories';
@endphp
<div class="px-3 page-content" id="categories-listing">
    <div id="search-bar" class="search mt-2 bg-light" vcloak>
        <div class="d-flex">
            <div class="flex-grow-1">
                <div id="search" class="pr-2">
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <div class="flex-shrink-0">
                <button title="" type="button" class="btn btn-primary" data-original-title="Search">
                    <i class="fas fa-search"></i>
                </button>
                @can('create-categories')
                    <button type="button" id="create_category" class="btn btn-secondary" @click="$refs.createCategory.show()">
                        <i class="fas fa-plus"></i> {{ __('Category') }}
                    </button>
                @endcan
            </div>
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

    @can('create-categories')
        <b-modal hidden 
                ref="createCategory" 
                title="{{ __('Create Category') }}" 
                ok-title="{{ __('Save') }}"
                apiRoute="{{route($config->routes->categoryListApi)}}"
                centered
                @ok="onSubmit" 
                @hidden="onClose"
        >
            <div class="form-group">
                {!!Form::label('name', __('Category Name'))!!}
                {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'addCategory.name',
                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addCategory.errors.name}'])!!}
                <small class="form-text text-muted" v-if="! addCategory.errors.name">
                    {{ __('The category name must be distinct.') }}
                </small>
                <div class="invalid-feedback" v-for="name in addCategory.errors.name">@{{name}}</div>
            </div>
            <div class="form-group">
                {!! Form::label('status', __('Status')) !!}
                {!! Form::select('status', ['ACTIVE' => __('active'), 'INACTIVE' => __('inactive')], null, ['id' => 'status',
                'class' => 'form-control', 'v-model' => 'addCategory.status', 'v-bind:class' => '{"form-control":true, "is-invalid":addCategory.errors.status}']) !!}
                <div class="invalid-feedback" v-for="status in addCategory.errors.status">@{{status}}</div>
            </div>
        <b-modal>
    @endcan
</div>

@section('js')
    <script src="{{mix('js/processes/categories/index.js')}}"></script>
@append
