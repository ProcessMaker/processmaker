@extends('layouts.layout')

@section('content')

<h3>User Profile</h1>
<div class="container bg-light">
  <form>
  <div class="row">
    <div class="col">
      <input type="text" class="form-control" placeholder="First name">
    </div>
    <div class="col">
      <input type="text" class="form-control" placeholder="Last name">
    </div>
  </div>
</form>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection
@section('js')
  <script>

</script>
@endsection
