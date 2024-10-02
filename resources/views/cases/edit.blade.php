@extends('layouts.layout')

@section('title')
  {{ __('Case Detail') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_cases')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
      __('Cases') => route('cases.index'),
  ]])
@endsection

@section('content')
<div id="case-detail" class="containe-fluid mr-3 ml-3 px-3 bg-light">
  <case-detail></case-detail>
</div>
@endsection

@section('js')
  <script>
    const data = @json($request->getRequestData());
    const requestId = @json($request->getKey());
    const request = @json($request->getRequestAsArray());
    const files = @json($files);
    const canCancel = @json($canCancel);
    const canViewPrint = @json($canPrintScreens);
    const errorLogs = @json(['data' => $request->getErrors()]);
    const processId = @json($request->process->id);
    const canViewComments = @json($canViewComments);
    const comentable_type = @json(get_class($request));
  </script>
  <script src="{{mix('js/composition/cases/casesDetail/edit.js')}}"></script>
@endsection
