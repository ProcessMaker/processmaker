<div class="page-content mb-0" id="templatesIndex">
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
             @canany(['import-process-templates', 'create-process-templates'])
                <div class="d-flex ml-md-0 flex-column flex-md-row">
                    @can('import-process-templates')
                        <div class="mb-3 mb-md-0 ml-md-2">
                            <a
                                href="#"
                                aria-label="{{ __('Import Template') }}"
                                id="import_template"
                                class="btn btn-outline-secondary w-100"
                                @click="goToImport"
                            >
                                <i class="fas fa-file-import"></i> {{__('Import')}}
                            </a>
                        </div>
                    @endcan
                </div>
            @endcan
        </div>
    </div>


    <div class="container-fluid">
        <process-templates-listing
            ref="templateListing"
            :filter="filter"
            {{-- status="archived" --}}
            v-on:edit="edit"
            v-on:reload="reload"
            :permission="{{ \Auth::user()->hasPermissionsFor('process-templates') }}"
            :current-user-id="{{ \Auth::user()->id }}"
            is-documenter-installed="{{\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()}}"
        ></process-templates-listing>
    </div>
</div>

@section('js')
    <script src="{{mix('js/templates/index.js')}}"></script>
@append
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
