<div class="px-3 page-content" id="categorizedList">
    @php
        $firstTab = $secondTab = 'nav-item nav-link';
        $firstContent = $secondContent = 'tab-pane fade show';

        $catListWebRoute = str_replace('.edit', '.index', $catConfig->routes->editCategoryWeb);
        $showCategoriesTab = $catListWebRoute === \Request::route()->getName() || $listConfig->countCategories === 0
                            ? true
                            : false;

        if ($showCategoriesTab) {
            $secondTab.=' active';
            $secondContent.=' active';
        } else {
            $firstTab.=' active';
            $firstContent.=' active';
        }
    @endphp
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="{{$firstTab}}" id="nav-sources-tab" data-toggle="tab" href="#nav-sources" role="tab"
               onclick="loadProcess()" aria-controls="nav-sources" aria-selected="true">
                {{ $tabs[0] ?? __('Resources') }}
            </a>
        </li>
        @if (isset($listTemplates) && $listTemplates->permissions['view'])
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-templates-tab" data-toggle="tab" href="#nav-templates"
                role="tab" onclick="loadTemplates()" aria-controls="nav-templates" aria-selected="true">
                    {{ $tabs[1] ?? __('Templates') }}
                </a>
            </li>
            @if ($catConfig->permissions['view'])
            <li class="nav-item">
                <a class="{{$secondTab}}" id="nav-categories-tab" data-toggle="tab" href="#nav-categories"
                role="tab" onclick="loadCategory()" aria-controls="nav-categories" aria-selected="true">
                    {{ $tabs[2] ?? __('Categories') }}
                </a>
            </li>
            @endif
            @isset($tabs[2])
                <li class="nav-item">
                    <a class="nav-item nav-link" id="nav-archived-tab" data-toggle="tab" href="#nav-archived"
                        role="tab" onclick="loadArchivedProcess()" aria-controls="nav-archived" aria-selected="true">
                        {{ $tabs[3] ?? __('Archived Processes') }}
                    </a>
                </li>
            @endisset
        @elseif (isset($listScreenTemplates))
            @if ($catConfig->permissions['view'])
                <li class="nav-item">
                    <a class="nav-item nav-link" id="nav-categories-tab" data-toggle="tab" href="#nav-categories"
                    role="tab" onclick="loadCategory()" aria-controls="nav-categories" aria-selected="true">
                        {{ $tabs[1] ?? __('Categories') }}
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a class="{{$secondTab}}" id="nav-myTemplates-tab" data-toggle="tab" href="#nav-myTemplates"
                role="tab" onclick="loadMyScreenTemplates()" aria-controls="nav-myTemplates" aria-selected="true">
                    {{ $tabs[2] ?? __('My Templates') }}
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-item nav-link"
                    id="nav-publicTemplates-tab"
                    data-toggle="tab"
                    href="#nav-publicTemplates"
                    role="tab"
                    onclick="loadPublicScreenTemplates()"
                    aria-controls="nav-publicTemplates"
                    aria-selected="true">
                        {{ $tabs[3] ?? __('Public Templates') }}
                </a>
            </li>
        @else
            @if ($catConfig->permissions['view'] && $catConfig->routes->itemsIndexWeb !== "data-sources.index")
            <li class="nav-item">
                <a class="{{$secondTab}}" id="nav-categories-tab" data-toggle="tab" href="#nav-categories"
                role="tab" onclick="loadCategory()" aria-controls="nav-categories" aria-selected="true">
                    {{ $tabs[2] ?? __('Categories') }}
                </a>
            </li>
            @endif
            @if (
                array_key_exists('view', $catConfig->permissions) &&
                $catConfig->permissions['view'] &&
                $catConfig->routes->itemsIndexWeb === "data-sources.index"
            )
            <li class="nav-item">
                <a class="{{$secondTab}}" id="nav-categories-tab" data-toggle="tab" href="#nav-categories"
                role="tab" onclick="loadCategory()" aria-controls="nav-categories" aria-selected="true">
                    {{ $tabs[1] ?? __('Categories') }}
                </a>
            </li>
            @endif
            @if (
                array_key_exists('view-data-sources', $catConfig->permissions) &&
                $catConfig->permissions['view-data-sources'] &&
                $catConfig->routes->itemsIndexWeb === "data-sources.index"
            )
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-archived-tab" data-toggle="tab" href="#nav-archived"
                role="tab" onclick="loadArchivedProcess()" aria-controls="nav-archived" aria-selected="true">
                    {{ $tabs[2] ?? __('Logs') }}
                </a>
            </li>
            @endif
            @isset($tabs[3])
                <li class="nav-item">
                    <a class="nav-item nav-link" id="nav-archived-tab" data-toggle="tab" href="#nav-archived"
                    role="tab" onclick="loadArchivedProcess()" aria-controls="nav-archived" aria-selected="true">
                        {{ $tabs[3] ?? __('Archived Processes') }}
                    </a>
                </li>
            @endisset
        @endif
    </ul>

    <div>
        <div class="tab-content">
            <div class="{{$firstContent}}" id="nav-sources" role="tabpanel" aria-labelledby="nav-sources-tab">
                <div class="card card-body p-3 border-top-0">
                    {{ $itemList }}
                </div>
            </div>
            @if(isset($templatesList))
                @can('view-process-templates')
                <div class="tab-pane fade" id="nav-templates" role="tabpanel" aria-labelledby="nav-templates-tab">
                    <div class="card card-body p-3 border-top-0">
                        {{ $templatesList }}
                    </div>
                </div>
                @endcan
                <div class="{{$secondContent}}" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
                <div class="card card-body p-3 border-top-0">
                    {{ $categoryList }}
                </div>
                </div>
                @isset($tabs[3])
                    <div class="tab-pane fade" id="nav-archived" role="tabpanel" aria-labelledby="nav-archived-tab">
                        <div class="card card-body p-3 border-top-0">
                            {{ $archivedList }}
                        </div>
                    </div>
                @endisset
            @elseif(isset($listScreenTemplates))
                <div
                    class="{{$secondContent}}"
                    id="nav-categories"
                    role="tabpanel"
                    aria-labelledby="nav-categories-tab"
                >
                    <div class="card card-body p-3 border-top-0">
                        {{ $categoryList }}
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-myTemplates" role="tabpanel" aria-labelledby="nav-myTemplates-tab">
                    <div class="card card-body p-3 border-top-0">
                        {{ $myTemplatesList }}
                    </div>
                </div>
                <div
                    class="tab-pane fade"
                    id="nav-publicTemplates"
                    role="tabpanel"
                    aria-labelledby="nav-publicTemplates-tab"
                >
                    <div class="card card-body p-3 border-top-0">
                        {{ $publicTemplatesList }}
                    </div>
                </div>
            @else
                <div class="{{$secondContent}}" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
                    <div class="card card-body p-3 border-top-0">
                        {{ $categoryList }}
                    </div>
                </div>
                @isset($tabs[2])
                    <div class="tab-pane fade" id="nav-archived" role="tabpanel" aria-labelledby="nav-archived-tab">
                        <div class="card card-body p-3 border-top-0">
                            {{ $archivedList }}
                        </div>
                    </div>
                @endisset
            @endif
        </div>
    </div>
</div>

@section('js')
    <script>
      loadCategory = function () {
        ProcessMaker.EventBus.$emit("api-data-category", true);
      };
      loadProcess = function () {
        ProcessMaker.EventBus.$emit("api-data-process");
      };
      loadArchivedProcess = function () {
        ProcessMaker.EventBus.$emit("api-data-archived-process");
      };
      loadTemplates = function () {
        ProcessMaker.EventBus.$emit("api-data-process-templates");
      }
      if ({{$listConfig->countCategories}} === 0) loadCategory();
      loadMyScreenTemplates = function () {
        ProcessMaker.EventBus.$emit("api-data-my-screen-templates");
      };
      loadPublicScreenTemplates = function () {
        ProcessMaker.EventBus.$emit("api-data-public-screen-templates");
      };
    </script>
@append
