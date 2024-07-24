@extends('layouts.layout', ['content_margin' => '', 'overflow-auto' => ''])
@section('content')
  <div class="mobile-flex-container">
    @include('layouts.navbarMobile')
    <div class="mobile-container">
      @yield('content_mobile')
    </div>
  </div>
@endsection
<script>
</script>
<!-- Hide Sidebar and Navbar -->
<style media="screen">
  #sidebar {
    display: none;
  }
  #navbar {
    display: none;
  }
  .mobile-flex-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    min-width: 360px;
  }
  .mobile-container {
    flex: 1;
    height: 100vh;
    overflow: auto;
  }
</style>
@yield('css')
