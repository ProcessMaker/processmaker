<div class="page-content mb-0" id="archivedProcess">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <pmql-input
                  ref="pmql_input"
                  search-type="processes"
                  :value="pmql"
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

    <div class="container-fluid">
        <archived-processes-list
            ref="processListing"
            :filter="filter"
            :pmql="pmql"
            status="archived"
            v-on:reload="reload"
            :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
        ></archived-processes-list>
    </div>
</div>

@section('js')
    @vite('resources/js/processes/archived.js')
@append
