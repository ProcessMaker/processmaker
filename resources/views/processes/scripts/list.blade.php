    <div class="page-content mb-0" id="scriptIndex">
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
                @can('create-scripts')
                    <create-script-modal
                        :count-categories='@json($config->countCategories)'
                        :script-executors='@json($config->scriptExecutors)'
                    ></create-script-modal>
                @endcan
            </div>
        </div>

        <div class="container-fluid">
            <script-listing :filter="filter"
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
