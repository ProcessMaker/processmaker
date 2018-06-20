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
        <li><avatar id="topnav-avatar" uid="{{Auth::user()->uid}}"></avatar></li>
      </li>
    </ul>
</nav>
