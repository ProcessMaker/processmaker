@extends('layouts.layout')

@section('content')
<div class="mobile-container">
  @yield('content_mobile')
</div>
@endsection


<script>
  const browser = navigator.userAgent;
  const isMobileDevice  = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
  if (isMobileDevice) {
    document.cookie = "isMobile=true"
  }
</script>
<!-- Hide Sidebar and Navbar -->
<style media="screen">
  #sidebar {
    display: none !important;
  }
  #navbar {
    display: none !important;
  }
</style>

@yield('css')
