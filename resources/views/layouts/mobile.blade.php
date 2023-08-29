@extends('layouts.layout')

@section('content')
@include('layouts.navbarMobile')
<div class="mobile-container">
  @yield('content_mobile')
</div>
@endsection


<script>
  const browser = navigator.userAgent;
  const isMobileDevice  = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
  document.cookie = "isMobile=false"
  if (isMobileDevice) {
    document.cookie = "isMobile=true"
  }
</script>
<!-- Hide Sidebar and Navbar -->
<style media="screen">
  #sidebar {
    display: none;
  }
  #navbar {
    display: none;
  }
</style>

@yield('css')
