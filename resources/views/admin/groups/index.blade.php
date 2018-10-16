@extends('layouts.layout')

@section('title')
  {{__('Edit Groups')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container mt-4">
    <div class="row mt-5">
        <div class="col-1">
        <h3>{{__('Groups')}}</h3>
        </div>
        <div class="col-3">
        <input type="text" class="form-control" placeholder="&#xf0e0; Search">
        </div>
        <div class="col"></div>
        <button type="submit" class="btn btn-secondary mr-3"> <i class="fas fa-plus"></i> Group</button>
    </div>
    <div id="groups-listing">
        <groups-listing></groups-listing>
    </div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/index.js')}}"></script>
@endsection
