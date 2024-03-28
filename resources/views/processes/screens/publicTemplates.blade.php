    <div class="page-content mb-0" id="publicTemplatesIndex">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <pmql-input
                        ref="pmql_input"
                        search-type="screen_templates"
                        :value="pmql"
                        :url-pmql="urlPmql"
                        :filters-value="pmql"
                        :ai-enabled="false"
                        :show-filters="false"
                        :aria-label="$t('Search')"
                        @submit="onNLQConversion"
                        @pmqlchange="onChange">
                    </pmql-input>
                </div>
                @canany(['import-screen-templates', 'create-screen-templates'])
                    <div class="d-flex ml-md-0 flex-column flex-md-row">
                        @can('import-screen-templates')
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

        <public-templates-listing ref="publicTemplatesListing"
                        :filter="filter"
                        :pmql="pmql"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens', 'projects', 'screen-templates') }}"
                        v-on:reload="reload">
        </public-templates-listing>
    </div>

@section('js')
    <script src="{{mix('js/processes/screen-templates/publicTemplates.js')}}"></script>

@append
