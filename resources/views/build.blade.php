@extends('layouts.layout')

@section('content')

<h1>build</h1>
<div class="container">
                <my-vuetable
                  api-url="/test"></my-vuetable>
            </div>
@endsection

@section('sidebar')
  @include('sidebars.build')
@endsection
@section('js')
  <script>

</script>
@endsection
