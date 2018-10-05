@extends('layouts.layout', ['title' => 'Search'])

@Section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@Section('content')
@endsection

@Section('js')
@endsection