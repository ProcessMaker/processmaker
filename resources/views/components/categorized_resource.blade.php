<div class="px-3 page-content" id="categorizedList">
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
                    {{ $itemList }}
                </div>
            </div>
            <div class="tab-pane fade show" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
                <div class="card card-body">
                    {{ $categoryList }}
                </div>
            </div>
        </div>
    </div>
</div>

