<div class="px-3 page-content" id="{{$id}}">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-item nav-link active" id="nav-sources-tab" data-toggle="tab" href="#nav-sources" role="tab"
                aria-controls="nav-sources" aria-selected="true">
                {{ $tabs[0] ?? __('Resources') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-item nav-link" id="nav-categories-tab" data-toggle="tab" href="#nav-categories" role="tab"
                aria-controls="nav-categories" aria-selected="true">
                {{ $tabs[1] ?? __('Categories') }}
            </a>
        </li>
    </ul>

    <div class="mt-3">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="nav-sources" role="tabpanel" aria-labelledby="nav-sources-tab">
                <div class="card card-body">
                    <div class="row">
                        <div class="col">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                                <input v-model="filter" class="form-control" placeholder="{{ __('Search') }}...">
                            </div>
                        </div>
                        <div class="col-8 ">
                            @can($permissions['create-resource'])
                            <button type="button" href="#" id="create_datasource" class="btn btn-secondary float-right"
                                data-toggle="modal" data-target="#createDatasource">
                                <i class="fas fa-plus"></i> {{$labels['resource'] ?? __('Resource')}}
                            </button>
                            @endcan
                        </div>
                    </div>
                    <datasource-list ref="datasourceListing" :filter="filter"
                        :permission="{{ \Auth::user()->hasPermissionsFor($permissions['resources']) }}"
                        v-on:reload="reload">
                    </datasource-list>
                </div>
            </div>
            <div class="tab-pane fade show" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
                <div class="card card-body">
                    <div class="row">
                        <div class="col">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                                <input v-model="filter" class="form-control" placeholder="{{ __('Search') }}...">
                            </div>
                        </div>
                        <div class="col-8">
                            @can($permissions['create-category'] ?? 'create-category')
                            <button type="button" id="create_category" class="btn btn-secondary float-right"
                                data-toggle="modal" data-target="#createCategory">
                                <i class="fas fa-plus"></i> {{ $labels['category'] ?? __('Category') }}
                            </button>
                            @endcan

                        </div>
                    </div>
                    <categories-listing ref="list" :filter="filter" api-route="{{$categories['api'][0]}}"
                        :permission="{{\Auth::user()->hasPermissionsFor($permissions['categories'])}}"
                        location="{{$categories['location']}}"
                        include="{{isset($categories['api'][1]) ? $categories['api'][1]['include'] : ''}}"
                        label-count="{{$categories['count-label']}}" count="{{$categories['count-children']}}">
                    </categories-listing>
                </div>
            </div>
        </div>
    </div>
</div>