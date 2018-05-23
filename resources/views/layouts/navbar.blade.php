<nav class="navbar navbar-expand-lg navbar-light bg-light p-0">
  <a class="navbar-brand" href="#"><img src="/img/logo.png" style="max-width:40px"></a>
  <div class="mr-auto collapse navbar-collapse " id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto p-3">
      <li class="nav-item">
        <a class="nav-link" href="{{ url('home') }}">Request</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ url('task') }}">Task</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/{{ url('process') }}">Process</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ url('admin') }}">Admin</a>
      </li>
    </ul>
    <component id="navbar-request-button" v-bind:is="'request-modal'"></component>
      <ul class="navbar-nav">
        <li class="nav-item dropdown" style="white-space:nowrap">
        <a class="nav-link admin-menu" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span style="font-size:18px; font-weight:600; margin-bottom:0;">
            John Bunton
          </span>
        </a>

        <li><img src="/img/avatar.png" style="max-width:46px"></li>
        <li style="border-left:1px solid #ccc; margin:10px 30px"></li>
        <button class="nav_icon btn"><i class="fa fa-envelope" aria-hidden="true"></i></button>
        <button class="nav_icon btn"><i class="fa fa-bell" aria-hidden="true"></i></button>
      </li>
    </ul>
  </div>
</nav>
