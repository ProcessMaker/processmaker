@extends('layouts.layout')

@section('content')


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
