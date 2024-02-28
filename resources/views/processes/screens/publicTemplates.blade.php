    <div class="page-content mb-0" id="publicTemplatesIndex">
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
            </div>
        </div>

        <public-templates-listing ref="publicTemplatesListing"
                        :filter="filter"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens', 'projects') }}"
                        v-on:reload="reload">
        </public-templates-listing>
    </div>

@section('js')
    <script src="{{mix('js/processes/screen-templates/publicTemplates.js')}}"></script>

@append
