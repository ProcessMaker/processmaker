<b-navbar id="navbar" v-cloak toggleable="lg" type="light" variant="light" class="d-print-none">
    <div class="d-flex d-none d-xs-block d-lg-none w-100">
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

        <b-navbar-nav class="d-flex align-items-center">
            @foreach(Menu::get('topnav')->items as $item)
                <b-nav-item href="{{ $item->url() }}" {{$item->isActive !== false ? 'active': ''}}>
                    {{$item->title}}
                </b-nav-item>
            @endforeach
        </b-navbar-nav>

        <b-navbar-nav class="d-flex align-items-center ml-auto">
            <b-nav-item class="d-block">
                <component id="navbar-request-button" v-bind:is="'request-modal'" url="{{ route('processes.index') }}" v-bind:permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"></component>
            </b-nav-item>

            <b-nav-item class="d-none d-lg-block">
                <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages">
                </notifications>
            </b-nav-item>
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
