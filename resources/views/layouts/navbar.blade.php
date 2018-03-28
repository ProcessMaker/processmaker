
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div><a href="#menu-toggle" id="menu-toggle" class="menu-toggle"><i class="fa fa-bars"></i></a></div>
  <a class="navbar-brand" href="#"><img src="/img/logo.png" style="max-width:40px"></a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="mr-auto collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
    <ul class="navbar-nav">
      <li class="nav-item active">
        <a class="nav-link" href="#">Execute <span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">Build</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">Manage</a>
      </li>
      <li style="border-left:1px solid #ccc; margin:10px 30px"></li>
      </ul>

      <ul class="navbar-nav">
        <li><img src="/img/avatar.png" style="max-width:40px"></li>
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
      </li>
    </ul>
  </div>
</nav>
