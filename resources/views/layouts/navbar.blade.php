
<nav class="navbar navbar-expand-lg navbar-light bg-light mt-0">
  <a class="navbar-brand" href="#"><img src="/img/logo.png" style="max-width:40px"></a>
  <div class="mr-auto collapse navbar-collapse " id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="/">Run</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/build">Build</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/manage">Manage</a>
      </li>
    </ul>

      <ul class="navbar-nav">
        <li class="nav-item dropdown" style="white-space:nowrap">
        <a class="nav-link dropdown-toggle admin-menu" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span style="font-size:14px; font-weight:600; margin-bottom:0;">
            John Bunton
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="#">Action</a>
          <a class="dropdown-item" href="#">Another action</a>
          <div class="dropdown-divider"></div>
           <a class="dropdown-item" href="{{route('logout')}}">Logout</a>
        </div>
        <li><img src="/img/avatar.png" style="max-width:40px"></li>
        <li style="border-left:1px solid #ccc; margin:10px 30px"></li>
        <button class="nav_icon btn"><i class="fa fa-envelope" aria-hidden="true"></i></button>
        <button class="nav_icon btn"><i class="fa fa-bell" aria-hidden="true"></i></button>
      </li>
    </ul>
  </div>
</nav>
