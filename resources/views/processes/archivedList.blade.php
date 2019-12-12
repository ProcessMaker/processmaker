<div class="page-content mb-0" id="archivedProcess">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" data-original-title="Search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="container-fluid">
        <archived-processes-list
            ref="processListing"
            :filter="filter"
            status="inactive"
            v-on:reload="reload"
            :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
        ></archived-processes-list>
    </div>
</div>

@section('js')
    <script src="{{mix('js/processes/archived.js')}}"></script>
@append
