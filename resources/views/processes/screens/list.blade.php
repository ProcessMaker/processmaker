    <div class="page-content mb-0" id="screenIndex">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                    <pmql-input
                      id="search-box"
                      ref="pmql_input"
                      search-type="screens"
                      :value="filter"
                      :url-pmql="urlPmql"
                      :ai-enabled="false"
                      :show-filters="false"
                      :aria-label="$t('Search')"
                      @submit="onNLQConversion">
                    </pmql-input>
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
                            ></create-screen-modal>
                        @endcan
                    </div>
                @endcan
            </div>
        </div>

        <screen-listing ref="screenListing"
                        :filter="filter"
                        :pmql="pmql"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}"
                        v-on:reload="reload">
        </screen-listing>
    </div>

@section('js')
    <script src="{{mix('js/processes/screens/index.js')}}"></script>

@append
