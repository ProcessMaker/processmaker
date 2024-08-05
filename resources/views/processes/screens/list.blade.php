    <div class="page-content mb-0" id="screenIndex">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <div id="search" class="mb-3 mb-md-0">
                        <div class="input-group w-100">
                            <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}" data-cy="input-search">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                @canany(['import-screens', 'create-screens'])
                    <div class="d-flex ml-md-0 flex-column flex-md-row">
                        @can('import-screens')
                            <div class="mb-3 mb-md-0 ml-md-2">
                                <a href="#"  aria-label="{{ __('Import Screen') }}" class="btn btn-outline-secondary w-100" @click="goToImport" data-cy="button-import-screen">
                                    <i class="fas fa-file-import"></i> {{__('Import')}}
                                </a>
                            </div>
                        @endcan
                        @can('create-screens')
                            <create-screen-modal
                                :count-categories='@json($config->countCategories)'
                                :types='@json($config->types)'
                                is-projects-installed="{{\ProcessMaker\PackageHelper::isPackageInstalled(\ProcessMaker\PackageHelper::PM_PACKAGE_PROJECTS)}}"
                            ></create-screen-modal>
                        @endcan
                    </div>
                @endcan
            </div>
        </div>

        <screen-listing ref="screenListing"
                        :filter="filter"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens', 'projects', 'screen-templates') }}"
                        :current-user-id={{ \Auth::user()->id}}
                        :types='@json($config->types)'
                        v-on:reload="reload">
        </screen-listing>
    </div>

@section('js')
    <script src="{{mix('js/processes/screens/index.js')}}"></script>

@append
