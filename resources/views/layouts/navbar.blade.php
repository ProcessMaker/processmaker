<nav class="navbar navbar-expand-lg navbar-light bg-light p-0">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="{{ url('request') }}">{{__('Requests')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ url('task') }}">{{__('Tasks')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ url('process') }}">{{__('Processes')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ url('admin') }}">Admin</a>
      </li>
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
    </ul>

    <component id="navbar-request-button" v-bind:is="'request-modal'"></component>
    <notifications id="navbar-notifications-button" ref="hola" v-bind:is="'notifications'" v-bind:messages="messages"></notifications>

    <ul class="navbar-nav">
      <li class="break"></li>
      <li class="dropdown">
        <img class="avatar dropdown-toggle " id="topnav-avatar" src="/img/avatar.png" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
           <a class="dropdown-item drop-header"><img class="avatar-small" src="/img/avatar.png">{{\Auth::user()->firstname}} {{\Auth::user()->lastname}}</a>
           @foreach($dropdown_nav->items as $row)
              <a class="dropdown-item" href="{{ $row->url() }}"><i class="fas {{$row->attr('icon')}} fa-fw fa-lg"></i>{{$row->title}}</a>
           @endforeach
         </div>
      </li>
    </li>
  </ul>
</nav>
