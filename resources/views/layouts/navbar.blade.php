<b-navbar id="navbar" v-cloak toggleable="lg" type="light" variant="light" class="d-print-none">
    <div class="d-flex d-lg-none w-100">
        @php
            $loginLogo = \ProcessMaker\Models\Setting::getLogin();
        @endphp
        <b-navbar-brand href="#" class="d-lg-none pl-2"><img class="navbar-logo" src={{$loginLogo}}></b-navbar-brand>
        <b-navbar-toggle class="ml-auto" target="nav_collapse"></b-navbar-toggle>
    </div>

    <b-collapse is-nav id="nav_collapse">
        <confirmation-modal class="d-none d-lg-block" id="confirmModal" v-if='confirmShow' :title="confirmTitle" :message="confirmMessage"
                            :variant="confirmVariant" :callback="confirmCallback"
                            @close="confirmShow=false">
        </confirmation-modal>
        <session-modal id="sessionModal" v-show='sessionShow' :title="sessionTitle" :message="sessionMessage" :time="sessionTime" :warn-seconds="sessionWarnSeconds"
                @close="sessionShow=false">
        </session-modal>
        <div v-if="alerts.length > 0" class="alert-wrapper">
            <b-alert v-for="(item, index) in alerts" :key="index" class="d-none d-lg-block alertBox" :show="item.alertShow" :variant="item.alertVariant" dismissible fade @dismissed="alertDismissed(item)" @dismiss-count-down="alertDownChanged($event, item)" style="white-space:pre-line">@{{item.alertText}}</b-alert>
        </div>

        @php
            $menuItems = [];
            $existsMenuProvider = Menu::get('customtopnav') !== null;
            $customNav = $existsMenuProvider ? Menu::get('customtopnav')->items->all() : [];
            $defaultNav = Menu::get('topnav')->items->all();
            foreach(array_merge($customNav, $defaultNav) as $item) {
                $newItem = (array) $item;
                $newItem['link'] = $item->url();
                $itemsInCustom = array_filter($customNav, function ($el) use($item) {
                    return $el === $item;
                });
                $newItem['isCustom'] = count($itemsInCustom) > 0;
                $menuItems[] = $newItem;
            }
            // If a menu provider is installed, remove menu items from ProcessMaker but preserve any other (from packages, for example)
            if ($existsMenuProvider) {
                $menuItems = array_filter($menuItems, function ($item) use($customNav) {
                    $itemRoute = Route::getRoutes()->match(Request::create($item['link']));
                    $isCoreLink =  !$itemRoute->isFallBack && isset($itemRoute->action['controller']) && strpos($itemRoute->action['controller'], "ProcessMaker\\Http\\") === 0;
                    return !$isCoreLink || $item['isCustom'];
                });
            }
        @endphp

        <b-navbar-nav class="d-flex align-items-center">
                <b-nav-item v-for="item in {{ json_encode ($menuItems) }}"
                            :href="item.link"
                            :link-classes="item.attributes.class_link"
                            :target="item.attributes.target"
                            :active="item.isActive"
                >
                    <span v-html="item.title"></span>
                </b-nav-item>
        </b-navbar-nav>

        <b-navbar-nav class="d-flex align-items-center ml-auto">
            <b-nav-item class="d-block">
                <component id="navbar-request-button" v-bind:is="'request-modal'" url="{{ route('processes.index') }}" v-bind:permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"></component>
            </b-nav-item>

            @can('view-notifications')
                <b-nav-item class="d-none d-lg-block">
                    <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages">
                    </notifications>
                </b-nav-item>
            @endcan
            <li class="separator d-none d-lg-block"></li>
            <li class="d-none d-lg-block">
                @php
                    $items = [];
                    foreach ($dropdown_nav->items as $item ) {
                        $newItem = new stdClass();
                        $newItem->class = 'fas ' . $item->attr('icon') . ' fa-fw fa-lg';
                        $newItem->title = $item->title;
                        $newItem->url = $item->url();
                        $items[] = $newItem;
                    }
                    $items = json_encode($items);
                    $user = Auth::user();
                @endphp
                <navbar-profile :info="{{$user}}" :items="{{$items}}"></navbar-profile>
            </li>
        </b-navbar-nav>
    </b-collapse>
</b-navbar>
<style lang="scss" scoped>
    .separator {
        border-right: 1px solid rgb(227, 231, 236);
        height: 30px;
        margin-left: 0.5rem;
        margin-right: 1rem;
    }
    #navbar { margin-left:-10px }
</style>
