    <div class="page-content mb-0" id="myTemplatesIndex">
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
            </div>
        </div>

        <my-templates-listing ref="myTemplatesListing"
                        :filter="filter"
                        :pmql="pmql"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens', 'projects', 'screen-templates') }}"
                        v-on:reload="reload">
        </my-templates-listing>
    </div>

@section('js')
    <script src="{{mix('js/processes/screen-templates/myTemplates.js')}}"></script>

@append
