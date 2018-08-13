<div id="navbar" v-cloak>
  <b-navbar toggleable="md" type="light" variant="light">
    <b-alert :show="alertShow" id="alertBox" :variant="alertVariant" @dismissed="alertShow = false" dismissible>@{{alertText}}</b-alert>
    <b-navbar-nav>
      @foreach(Menu::get('topnav')->items as $item)
      <b-nav-item href="{{ $item->url() }}" {{$item->isActive !== false ? 'active': ''}}>{{$item->title}}</b-nav-item>
      @endforeach
      </b-navbar-nav>
      <b-navbar-nav class="ml-auto">
      <li class="nav-item">
        <component id="navbar-request-button" v-bind:is="'request-modal'"></component>
      </li>

      <li class="nav-notification">
      <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages"></notifications>
      </li>
      <li class="seperator"></li>
      <li class="dropdown">
      <img class="avatar dropdown-toggle" id="topnav-avatar" src="/img/avatar.png" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
         <a class="dropdown-item drop-header"><img class="avatar-small" src="/img/avatar.png">{{\Auth::user()->firstname}} {{\Auth::user()->lastname}}</a>
         @foreach($dropdown_nav->items as $row)
            <a class="dropdown-item" href="{{ $row->url() }}"><i class="fas {{$row->attr('icon')}} fa-fw fa-lg"></i>{{$row->title}}</a>
         @endforeach
       </div>
      </li>
    </b-navbar-nav>
  </b-navbar>
</div>

<style lang="scss" scoped>
  .seperator{ 
    border-left: 1px solid rgb(227,231,236) ;
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