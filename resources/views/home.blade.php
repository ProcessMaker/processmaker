@extends('layouts.layout')

@section('content')

<h1>Execute</h1>
<div class="container">
                <my-vuetable
                  api-url="/test"></my-vuetable>
            </div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection
@section('js')
  <script>

</script>
@endsection
