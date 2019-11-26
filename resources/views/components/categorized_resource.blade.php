<div class="px-3 page-content" id="categorizedList">
    @php
        $firstTab = $secondTab = 'nav-item nav-link';
        $firstContent = $secondContent = 'tab-pane fade show';

        $catListWebRoute = str_replace_last('.edit', '.index', $catConfig->routes->editCategoryWeb);
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
               aria-controls="nav-sources" aria-selected="true">
                {{ $tabs[0] ?? __('Resources') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="{{$secondTab}}" id="nav-categories-tab" data-toggle="tab" href="#nav-categories"
               role="tab" onclick="loadCategory()" aria-controls="nav-categories" aria-selected="true">
                {{ $tabs[1] ?? __('Categories') }}
            </a>
        </li>
    </ul>

    <div>
        <div class="tab-content">
            <div class="{{$firstContent}}" id="nav-sources" role="tabpanel" aria-labelledby="nav-sources-tab">
                <div class="card card-body p-3 border-top-0">
                    {{ $itemList }}
                </div>
            </div>
            <div class="{{$secondContent}}" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
                <div class="card card-body p-3 border-top-0">
                    {{ $categoryList }}
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
    <script>
      loadCategory = function () {
        ProcessMaker.EventBus.$emit("api-data-category", true);
      };
      if ({{$listConfig->countCategories}} === 0) loadCategory();
    </script>
@append
