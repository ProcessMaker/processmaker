@extends('layouts.layout')

@section('title')
{{__($title)}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('breadcrumbs')
@include('shared.breadcrumbs', ['routes' => [
    __('Requests') => route('requests.index'),
    function() use ($title) { return [__($title), null]; }
]])
@endsection
@section('content')
<div class="px-3 page-content mb-0" id="requests-listing">
    <div class="row">
        <div class="col-sm-12">
            <counter-card-group>
                <counter-card
                    color="info"
                    icon="id-badge"
                    link="{{ route('requests_by_type', ['type' => '']) }}"
                    title="My Requests"
                    url='requests?total=true&pmql=(status = "In Progress") AND (requester = "{{ Auth::user()->username }}")'
                ></counter-card>
                <counter-card
                    color="success"
                    icon="clipboard-list"
                    link="{{ route('requests_by_type', ['type' => 'in_progress']) }}"
                    title="In Progress"
                    url='requests?total=true&pmql=(status = "In Progress")'
                ></counter-card>
                <counter-card
                    color="primary"
                    icon="clipboard-check"
                    link="{{ route('requests_by_type', ['type' => 'completed']) }}"
                    title="Completed"
                    url='requests?total=true&pmql=(status = "Completed")'
                ></counter-card>
                @can('view-all_requests')
                    <counter-card
                        color="secondary"
                        icon="clipboard"
                        link="{{ route('requests_by_type', ['type' => 'all']) }}"
                        title="All Requests"
                        url='requests?total=true'
                    ></counter-card>
                @endcan
            </counter-card-group>


            <div id="search-bar" class="search advanced-search mb-2">
              <div class="d-flex">
                <div class="flex-grow-1">
                  <pmql-input
                    ref="pmql_input"
                    search-type="requests"
                    :value="pmql"
                    :url-pmql="urlPmql"
                    :filters-value="pmql"
                    :ai-enabled="false"
                    :show-filters="true"
                    :aria-label="$t('Advanced Search (PMQL)')"
                    :param-status="status" 
                    :param-requester="requester" 
                    :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}" 
                    @submit="onNLQConversion"
                    @filterspmqlchange="onFiltersPmqlChange">

                    <template v-slot:left-buttons>
                      <div class="d-flex">
                        <div class="d-flex mr-1" v-for="addition in additions">
                          <component class="d-flex" :is="addition" :permission="{{ Auth::user()->hasPermissionsFor('users', 'groups') }}"></component>
                        </div>
                      </div>
                    </template>

                  </pmql-input>
                </div>
              </div>
            </div>

            <requests-listing ref="requestList" :filter="filter" :pmql="fullPmql"></requests-listing>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    //Data needed for default search
    window.Processmaker.user = @json($currentUser);
    window.Processmaker.status = '{{ $type }}';
</script>
<script src="{{mix('js/requests/index.js')}}"></script>
@endsection

@section('css')
<style>
    .has-search .form-control {
        padding-left: 2.375rem;
    }

    .has-search .form-control-feedback {
        position: absolute;
        z-index: 2;
        display: block;
        width: 2.375rem;
        height: 2.375rem;
        line-height: 2.375rem;
        text-align: center;
        pointer-events: none;
        color: #aaa;
    }

    .card-border {
        border-radius: 4px !important;
    }

    .card-size-header {
        max-width: 90px;
    }
    .option__image {
        width:27px;
        height: 27px;
        border-radius: 50%;
    }
    .initials {
        display:inline-block;
        text-align: center;
        font-size: 12px;
        max-width:25px;
        max-height: 25px;
        min-width:25px;
        min-height: 25px;
        border-radius: 50%;
    }
</style>
@endsection
