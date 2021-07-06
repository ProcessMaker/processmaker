<div class="page-content mb-0" id="processIndex">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            @canany(['import-processes', 'create-processes'])
                <div class="d-flex ml-md-0 flex-column flex-md-row">
                    @can('import-processes')
                        <div class="mb-3 mb-md-0 ml-md-2">
                            <a href="#" id="import_process" class="btn btn-outline-secondary w-100" @click="goToImport">
                                <i class="fas fa-file-import"></i> {{__('Import')}}
                            </a>
                        </div>
                    @endcan
                    @can('create-processes')
                        <create-process-modal :count-categories="@json($config->countCategories)"></create-process-modal>
                    @endcan
                </div>
            @endcan
        </div>
    </div>


    <div class="container-fluid">
        <processes-listing
            ref="processListing"
            :filter="filter"
            status="{{ $config->status }}"
            v-on:edit="edit"
            v-on:reload="reload"
            :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
            is-documenter-installed="{{\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()}}"
        ></processes-listing>
    </div>
</div>

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>

@append
