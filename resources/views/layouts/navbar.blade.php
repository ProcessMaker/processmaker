<b-navbar id="navbar" v-cloak  toggleable="md" type="light" variant="light" class="d-print-none">
    <div class="d-flex d-block d-sm-none">
        @php
            $loginLogo = \ProcessMaker\Models\Setting::getLogin();
        @endphp
        <b-navbar-brand href="#"><img class="img-fluid" src={{$loginLogo}}></b-navbar-brand></td>
        <b-navbar-toggle target="nav_collapse"></b-navbar-toggle></td>
    </div>

    <b-collapse is-nav id="nav_collapse">
        <confirmation-modal class="d-none d-lg-block" id="confirmModal" v-if='confirmShow' :title="confirmTitle" :message="confirmMessage"
                            :variant="confirmVariant" :callback="confirmCallback"
                            @close="confirmShow=false">
        </confirmation-modal>
        <session-modal id="sessionModal" v-show='sessionShow' :title="sessionTitle" :message="sessionMessage" :time="sessionTime"
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
            <b-nav-item class="d-none d-lg-block">
                <component id="navbar-request-button" v-bind:is="'request-modal'" v-bind:permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"></component>
            </b-nav-item>

            <b-nav-item class="d-none d-lg-block">
                <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages">
                </notifications>
            </b-nav-item>
            <b-nav-item class="seperator d-none d-lg-block"></b-nav-item>
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
                <navbar-profile :info="{{$user}}"  :items="{{$items}}"></navbar-profile>
            </li>
        </b-navbar-nav>
    </b-collapse>
</b-navbar>
<style lang="scss" scoped>
    .seperator {
        border-right: 1px solid rgb(227, 231, 236);
        height: 30px;
    }
    #navbar { margin-left:-10px }
</style>
