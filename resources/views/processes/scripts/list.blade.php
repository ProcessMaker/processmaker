    <div class="page-content mb-0" id="scriptIndex">
        <div id="search-bar" class="search mb-3" vcloak>
            <div class="d-flex flex-column flex-md-row">
                <div class="flex-grow-1">
                  <pmql-input
                      id="search-box"
                      ref="pmql_input"
                      search-type="scripts"
                      :value="filter"
                      :url-pmql="urlPmql"
                      :ai-enabled="false"
                      :show-filters="false"
                      :aria-label="$t('Search')"
                      @submit="onNLQConversion">
                    </pmql-input>
                </div>
                @can('create-scripts')
                    <create-script-modal
                        :count-categories='@json($config->countCategories)'
                        :script-executors='@json($config->scriptExecutors)'
                    ></create-script-modal>
                @endcan
            </div>
        </div>

        <div class="container-fluid">
            <script-listing 
              :filter="filter"
              :pmql="pmql"
              :script-executors='@json($config->scriptExecutors)'
              :permission="{{ \Auth::user()->hasPermissionsFor('scripts') }}"
              ref="listScript"
              @delete="deleteScript">
            </script-listing>
        </div>
    </div>
    
@section('js')
    <script src="{{mix('js/processes/scripts/index.js')}}"></script>

@append
