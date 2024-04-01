<div id="navbar">
<b-navbar id="navbar1" v-cloak toggleable="lg" type="light" variant="light" class="d-print-none">
    <div class="d-flex d-lg-none w-100">
        @php
            $loginLogo = \ProcessMaker\Models\Setting::getLogin();
        @endphp
        <b-navbar-brand href="#" class="d-lg-none pl-2"><img class="navbar-logo" src={{$loginLogo}}></b-navbar-brand>
        <b-navbar-toggle class="ml-auto" :target="['nav-collapse', 'breadcrumbs-collapse']"></b-navbar-toggle>
    </div>

    <div class="d-flex d-lg-none w-100">
        @if(hasPackage('package-ai'))
        <global-search v-if="isMobile" class="w-100 small-screen"></global-search>
        @endif
    </div>

    <b-collapse is-nav id="nav-collapse">
        <confirmation-modal class="d-none d-lg-block" id="confirmModal" :show="confirmShow" :title="confirmTitle" :message="confirmMessage"
                            :variant="confirmVariant" :callback="confirmCallback" :size="confirmSize"
                            @close="confirmShow=false">
        </confirmation-modal>
        <message-modal class="d-none d-lg-block" id="messageModal" :show="messageShow" :title="messageTitle" :message="messageMessage"
                            :variant="messageVariant" :callback="messageCallback"
                            @close="messageShow=false">
        </message-modal>
        <session-modal id="sessionModal" :shown="sessionShow" :title="sessionTitle" :message="sessionMessage" :time="sessionTime" :warn-seconds="sessionWarnSeconds"
                @close="sessionShow=false">
        </session-modal>
        <div v-if="alerts.length > 0" class="alert-wrapper">
            <b-alert v-for="(item, index) in alerts" :key="index" class="d-none d-lg-block alertBox" :show="item.alertShow" :variant="item.alertVariant" dismissible fade @dismissed="alertDismissed(item)" @dismiss-count-down="alertDownChanged($event, item)" style="white-space:pre-line">
              <span v-if="item.showLoader" class="spinner-border spinner-border-sm mb-1 mr-2"></span>
              <span v-if="item.alertTitle"><p class="mt-0 mb-0"><b>@{{ item.alertTitle }}</b></p></span>
              <span>@{{item.alertText}}</span>
              <span v-if="item.alertLink"><a :href="item.alertLink">{{ __('Download') }}</a></span>
            </b-alert>
        </div>
        @php
            $menuItems = [];
            // Add here the package to add in the topNav menu
            $packagesList = ['package-analytics-reporting'];
            $existsMenuProvider = Menu::get('customtopnav') !== null;
            $items = $existsMenuProvider ? Menu::get('customtopnav')->items->all() : [];

            $customNav = [];
            foreach($items as $item) {
                if (!$item->hasParent()) {
                    $customNav[] = $item;
                    if ($item->hasChildren()) {
                        $item->childItems = $item->children();
                        $item->hasSubItems = true;
                    }
                    else {
                        $item->hasSubItems = false;
                    }
                }
            }

            $defaultNav = Menu::get('topnav')->items->all();
            foreach($defaultNav as $item) {
                $item->hasSubItems = false;
            }

            foreach(array_merge($customNav, $defaultNav) as $item) {
                $newItem = (array) $item;
                $newItem['link'] = $item->url();
                $itemsInCustom = array_filter($customNav, function ($el) use($item) {
                    return $el === $item;
                });
                $newItem['isCustom'] = count($itemsInCustom) > 0;
                $menuItems[] = $newItem;
            }
            // @todo make a refactor in the topNav reviewing the active() function
            // The add a menu the Request is always highligth
            if (in_array(Request::path(), $packagesList)) {
                $menuItems[0]['isActive'] = false;
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

        <b-navbar-nav class="d-flex align-items-center" style="z-index:100">
            <b-button
                v-if="isMobileDevice"
                class="btn btn-primary"
                variant="primary"
                style="text-transform: none"
                @click="switchToMobile()"
            >
                <i class="fas fa-mobile"></i>
                Switch to Mobile View
            </b-button>
            <template v-for="item in {{ json_encode ($menuItems) }}">
                <b-nav-item v-if="item.hasSubItems == false"
                            :href="item.link"
                            :link-classes="item.attributes.class_link"
                            :target="item.attributes.target"
                            :active="item.isActive"
                >
                    <span v-html="item.title"></span>
                </b-nav-item>
                <b-nav-item-dropdown v-else
                    :text="item.title"
                    toggle-class="nav-link-custom"
                    left
                >
                    <b-dropdown-item v-for="subItem in item.childItems"
                        :key="subItem.url"                    
                        :href="subItem.url"
                        :target="subItem.attributes.target"
                    >
                        <span :class="subItem.attributes.class_link" v-html="subItem.title"></span>
                    </b-dropdown-item>
                </b-nav-item-dropdown>

            </template>
        </b-navbar-nav>

        <b-navbar-nav class="d-flex align-items-center ml-auto">

            @if(hasPackage('package-ai'))
            <global-search v-if="!isMobile" class="d-none d-lg-block"></global-search>
            @endif

            @if (shouldShow('requestButton'))
            <component v-bind:is="'request-modal'" url="{{ route('processes.index') }}" v-bind:permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"></component>
            @endif

            <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages">
            </notifications>
            <li class="separator d-none d-lg-block"></li>
            <li class="d-none d-lg-block">
                @php
                    $user = Auth::user();
                    $permissions = json_encode([
                        'edit-personal-profile' => $user->can('edit-personal-profile'),
                    ]);
                @endphp
                <navbar-profile :info="{{$user}}" :permissions="{{$permissions}}"></navbar-profile>
            </li>
        </b-navbar-nav>
    </b-collapse>
</b-navbar>


<b-navbar id="navbar2" v-cloak toggleable="lg" type="light" variant="light" class="p-0 d-print-none">
    <b-collapse is-nav id="breadcrumbs-collapse" class="border-top border-bottom">
        @yield('breadcrumbs')
    </b-collapse>
</b-navbar>


</div>
<style lang="scss" scoped>
    .separator {
        border-right: 1px solid rgb(227, 231, 236);
        height: 30px;
        margin-left: 0.5rem;
        margin-right: 1rem;
    }
</style>
