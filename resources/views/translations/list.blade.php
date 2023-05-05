<div class="page-content mb-0" id="translationsIndex">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
             @canany(['import-process-translation', 'create-process-translation'])
                <div class="d-flex ml-md-0 flex-column flex-md-row">
                    @can('import-process-templates')
                        <div class="mb-3 mb-md-0 ml-md-2">
                            <a href="#" aria-label="{{ __('Import Translation') }}" id="import_translation" class="btn btn-outline-secondary w-100" @click="goToImport">
                                <i class="fas fa-file-import"></i> {{__('Import')}}
                            </a>
                        </div>
                    @endcan
                    @can('create-process-translation')
                        <div class="mb-3 mb-md-0 ml-md-2">
                            <a href="#" aria-label="{{ __('New Translation') }}" id="new_translation" class="btn btn-primary w-100" @click="goToNew">
                                {{__('+ Translation')}}
                            </a>
                        </div>
                    @endcan
                </div>
            @endcan
        </div>
    </div>


    <div class="container-fluid">
        <process-translations-listing
            ref="translationsListing"
            :filter="filter"
            v-on:edit="edit"
            v-on:reload="reload"
            :permission="{{ \Auth::user()->hasPermissionsFor('process-translations') }}"
            :current-user-id="{{ \Auth::user()->id }}"
        ></process-translations-listing>
    </div>
</div>

@section('js')
    <script src="{{mix('js/translations/index.js')}}"></script>
@append
