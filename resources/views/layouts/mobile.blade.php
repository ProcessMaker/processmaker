@extends('layouts.layout')

@section('content')
@include('layouts.navbarMobile')
<div class="mobile-container">
  @yield('content_mobile')
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
</style>

@yield('css')
