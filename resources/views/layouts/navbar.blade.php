<b-navbar id="navbar" v-cloak  toggleable="md" type="light" variant="light">
    <b-navbar-brand class="d-block d-sm-none" href="#"><img class="img-fluid" src={{asset(env('LOGIN_LOGO_PATH', '/img/processmaker_login.png'))}}></b-navbar-brand>
    <b-navbar-toggle target="nav_collapse"></b-navbar-toggle>

    <b-collapse is-nav id="nav_collapse">

    <confirmation-modal id="confirmModal" v-if='confirmShow' :title="confirmTitle" :message="confirmMessage"
                        :variant="confirmVariant" :callback="confirmCallback"
                        @close="confirmShow=false">
    </confirmation-modal>
    <b-alert :show="alertShow" id="alertBox" :variant="alertVariant" @dismissed="alertShow = false" dismissible>
        @{{alertText}}
    </b-alert>

    <b-navbar-nav>
        @foreach(Menu::get('topnav')->items as $item)
            <b-nav-item href="{{ $item->url() }}" {{$item->isActive !== false ? 'active': ''}}>
                {{$item->title}}
            </b-nav-item>
        @endforeach
    </b-navbar-nav>
    <b-navbar-nav class="ml-auto" id="mobileRight">
        <li class="nav-item">
            <component id="navbar-request-button" v-bind:is="'request-modal'"></component>
        </li>

        <li class="nav-notification d-none d-lg-block">
            <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages">
            </notifications>
        </li>
        <li class="seperator d-none d-lg-block"></li>
        <li class="nav-item align-self-center d-none d-lg-block">
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
                $user->avatar = null;
                if (Auth::user()->getAvatar()) {
                    $user->avatar  = Auth::user()->getAvatar();
                }
            @endphp
            <navbar-profile :info="{{$user}}"  :items="{{$items}}"></navbar-profile>
        </li>
    </b-navbar-nav>
</b-navbar>

<style lang="scss" scoped>
    .seperator {
        border-left: 1px solid rgb(227, 231, 236);
        height: 30px;
        margin-top: 17px;
    }
</style>
