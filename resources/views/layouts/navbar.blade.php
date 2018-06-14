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
    </ul>

    <component id="navbar-request-button" v-bind:is="'request-modal'"></component>

    <span class="navbar-text notifications">
      <i class="fas fa-bell" aria-hidden="true"></i>
    </span>
      <ul class="navbar-nav">
        <li class="break"></li>
        <li class="dropdown">
          <img class="avatar dropdown-toggle " id="navbarDropdown" src="/img/avatar.png" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
             <a class="dropdown-item" href="#"><i class="fas fa-user fa-fw"></i>{{__('Profile')}}</a>
             <a class="dropdown-item" href="{{route('logout')}}"><i class="fas fa-sign-out-alt fa-fw"></i>{{__('Log Out')}}</a>
             <a class="dropdown-item" href="#"><i class="fas fa-info fa-fw"></i>{{__('Help')}}</a>
             <a class="dropdown-item" href="#"><i class="fas fa-comments fa-fw"></i>{{__('Send Feedback')}}</a>
           </div>
        </li>
      </li>
    </ul>
</nav>
