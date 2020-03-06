<div id="deleted-users-listing">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <deleted-users-listing 
            ref="deletedUserListing" 
            :filter="filter" 
            :permission="{{ \Auth::user()->hasPermissionsFor('users') }}">
        </deleted-users-listing>
    </div>
</div>
