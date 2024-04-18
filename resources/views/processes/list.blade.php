<div class="page-content mb-0" id="processIndex">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
              <pmql-input
                ref="pmql_input"
                search-type="processes"
                :value="pmql"
                :url-pmql="urlPmql"
                :filters-value="pmql"
                :ai-enabled="false"
                :show-filters="false"
                :aria-label="$t('Search')"
                @submit="onNLQConversion">
              </pmql-input>
            </div>
            @canany(['import-processes', 'create-processes'])
                <div class="d-flex ml-md-0 flex-column flex-md-row">
                    @can('import-processes')
                        <div class="mb-3 mb-md-0 ml-md-2">
                            <a href="#" aria-label="{{ __('Import Process') }}" id="import_process" class="btn btn-outline-secondary w-100" @click="goToImport">
                                <i class="fas fa-file-import"></i> {{__('Import')}}
                            </a>
                        </div>
                    @endcan
                    @can('create-processes')
                        <select-template-modal
                            :type="__('Process')"
                            :count-categories="@json($config->countCategories)"
                            :package-ai="{{ hasPackage('package-ai') ? '1' : '0' }}"
                            is-projects-installed="{{\ProcessMaker\PackageHelper::isPackageInstalled(\ProcessMaker\PackageHelper::PM_PACKAGE_PROJECTS)}}"
                            is-ab-testing-installed="{{\ProcessMaker\PackageHelper::isPackageInstalled(\ProcessMaker\PackageHelper::PM_PACKAGE_AB_TESTING)}}"
                            >
                            </select-template-modal>
                    @endcan
                </div>
            @endcan
        </div>
    </div>
    @php
    $permissions = \Auth::user()->hasPermissionsFor(
        'processes',
        'process-templates',
        'pm-blocks',
        'projects',
        'additional-asset-actions'
    );
    @endphp
    <div class="container-fluid">
        <processes-listing
            ref="processListing"
            :filter="filter"
            :pmql="pmql"
            status="{{ $config->status }}"
            v-on:edit="edit"
            v-on:reload="reload"
            :permission="{{ $permissions }}"
            :current-user-id="{{ \Auth::user()->id }}"
            is-documenter-installed="{{\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()}}"
        ></processes-listing>
    </div>
</div>

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>

@append
