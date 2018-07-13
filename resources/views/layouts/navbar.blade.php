<div id="navbar" v-cloak>
  <b-navbar toggleable="md" type="light" variant="light">
    <b-navbar-nav>
      @foreach(Menu::get('topnav')->items as $item)
      <b-nav-item href="{{ $item->url() }}">{{$item->title}}</b-nav-item>
      @endforeach
      <li>
        @if(Session::has('_alert'))
          @php
          $icons = [
            'danger' =>  'fa-times-circle',
            'info' =>  'fa-info-circle',
            'warning' =>  'fa-exclamation-triangle',
            'success' =>  'fa-check'
          ];

          list($type,$message) = json_decode(Session::get('_alert'));
          @endphp
          <div class="alert alert-{{$type}} alert-dismissible fade show" role="alert">
            <i class="fas fa-{{$icons[$type]}}"></i> {{$message}}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        @endif
      </li>
      </b-navbar-nav>
      <b-navbar-nav class="ml-auto">
      <li class="nav-item">
        <component id="navbar-request-button" v-bind:is="'request-modal'"></component>
      </li>

      <li class="nav-item">
      <notifications id="navbar-notifications-button" v-bind:is="'notifications'" v-bind:messages="messages"></notifications>
      </li>
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
