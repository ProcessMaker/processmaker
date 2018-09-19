<div id="navbar" v-cloak>

    <b-navbar toggleable="md" type="light" variant="light">
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
        <b-navbar-nav class="ml-auto">
            <li class="nav-item">
                <component id="navbar-request-button" v-bind:is="'request-modal'"></component>
            </li>

            <li class="nav-notification">
                <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages">
                </notifications>
            </li>
            <li class="seperator"></li>
            <li>
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
</div>

<style lang="scss" scoped>
    .seperator {
        border-left: 1px solid rgb(227, 231, 236);
        height: 30px;
        margin-top: 17px;
    }

    .nav-item {
        padding-top: 5px;
    }

    .nav-notification {
        padding-top: 8px;
    }
</style>
