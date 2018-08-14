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
                <notifications id="navbar-notifications-button" v-bind:is="'notifications'"
                               v-bind:messages="messages"></notifications>
            </li>
            <li class="seperator"></li>
            <li class="dropdown">
                @if(\Auth::user()->getAvatar())
                    <img class="avatar dropdown-toggle" id="topnav-avatar" src="{{\Auth::user()->getAvatar()}}"
                         role="button"
                         data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item drop-header">
                            <img class="avatar-small" src="{{\Auth::user()->getAvatar()}}">
                            {{\Auth::user()->firstname}} {{\Auth::user()->lastname}}
                        </a>
                        @foreach($dropdown_nav->items as $row)
                            <a class="dropdown-item" href="{{ $row->url() }}">
                                <i class="fas {{$row->attr('icon')}} fa-fw fa-lg"></i>
                                {{$row->title}}
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="avatar-circle">
                        <span class="initials dropdown-toggle text-uppercase" id="topnav-avatar" role="button"
                              data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{Auth::user()->firstname[0]}}{{Auth::user()->lastname[0]}}
                        </span>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item drop-header">
                                <div class="avatar-circle small">
                                    <span class="initials-small text-uppercase" aria-haspopup="true"
                                          aria-expanded="false">
                                    {{Auth::user()->firstname[0]}}{{Auth::user()->lastname[0]}}
                                    </span>
                                    <span class="avatar-name">{{\Auth::user()->firstname}} {{\Auth::user()->lastname}}</span>
                                </div>


                            </a>
                            @foreach($dropdown_nav->items as $row)
                                <a class="dropdown-item" href="{{ $row->url() }}">
                                    <i class="fas {{$row->attr('icon')}} fa-fw fa-lg"></i>{{$row->title}}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

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

    .avatar-circle {
        width: 50px;
        height: 50px;
        background-color: #fbbe02;
        text-align: center;
        border-radius: 50%;
        -webkit-border-radius: 50%;
        -moz-border-radius: 50%;
        margin-left: 10px;
    }

    .avatar-circle .small {
        margin-left: -15px;
    }

    .initials {
        position: relative;
        font-size: 21px;
        line-height: 50px;
        color: #fff;
        font-weight: bold;
    }

    .initials-small {
        position: relative;
        font-size: 21px;
        line-height: 50px;
        color: #fff;
        font-weight: bold;
        margin-left: 10px;
    }

    .avatar-name {
        margin-left: 20px;
        font-size: 14px;
        font-weight: 600;
    }
</style>