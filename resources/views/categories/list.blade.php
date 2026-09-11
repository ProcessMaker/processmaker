@if ($config->permissions['view'])
<div class="page-content mb-0" id="categories-listing">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white search-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <input
                          id="search-box"
                          v-model="filter"
                          class="form-control pl-1 search-text search-input"
                          placeholder="{{__('Search here')}}"
                          aria-label="{{__('Search')}}"
                        >
                    </div>
                </div>
            </div>
            @if ($config->permissions['create'])
                <div class="d-flex ml-md-2 flex-column flex-md-row">
                    <button
                      type="button"
                      class="btn btn-secondary"
                      @click="showModal()"
                      aria-label="{{ __('Create Category') }}"
                      aria-haspopup="dialog"
                    >
                        <i class="fas fa-plus"></i> {{ __('Category') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <categories-listing
        ref="list"
        @reload="reload"
        :filter="filter"
        :permissions="{{ json_encode($config->permissions) }}"
        api-route="{{route($config->routes->categoryListApi)}}"
        load-on-start="{{$config->showCategoriesTab ?? true}}"
        include="{{$config->apiListInclude}}"
        label-count="{{$config->labels->countColumn}}"
        @edit="edit"
        count="{{$config->countField}}">
    </categories-listing>

    @if ($config->permissions['create'] || $config->permissions['edit'])
        <pm-modal ref="createCategoryModal" id="createCategoryModal" :title="getTitle()" @hidden="onClose" @ok.prevent="onSubmit" :ok-disabled="disabled" style="display: none;">
            <required></required>
            <div class="form-group">
                {{ html()->label(__('Category Name'), 'category-name') }}<small class="ml-1">*</small>
                {{ html()->text('name')->class('form-control')->attribute('v-model', 'name')->id('category-name')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.name}')->required()->attribute('aria-required', 'true') }}
                <small class="form-text text-muted" v-if="! errors.name">
                    {{ __('The category name must be unique.') }}
                </small>
                <div class="invalid-feedback" role="alert" v-for="name in errors.name">@{{name}}</div>
            </div>
            <div class="form-group">
                {{ html()->label(__('Status'), 'status') }}
                {{ html()->select('status', ['ACTIVE' => __('Active'), 'INACTIVE' => __('Inactive')])->id('status')->class('form-control')->attribute('v-model', 'status')->attribute('v-bind:class', '{"form-control":true, "is-invalid":errors.status}') }}
                <div class="invalid-feedback" role="alert" v-for="status in errors.status">@{{status}}</div>
            </div>
        </pm-modal>
    @endif

</div>

@section('js')
    <script>
      window.temporal = window.temporal || {};
      window.temporal.categoryListApi = "{{route($config->routes->categoryListApi)}}";
    </script>
    @vite(['resources/js/processes/categories/index.js'])
@append
@endif
@section('css')
<style>
.search-text {
    color: gray;
    border-color: #CDDDEE;
}
.search-text:hover {
        background-color: #FAFBFC;
        border-color: #CDDDEE;
}
.search-input {
    border-left: 0;
}
</style>
@endsection
