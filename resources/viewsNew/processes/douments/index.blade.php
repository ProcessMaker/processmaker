@extends('layouts.layout', ['title' => 'Documents'])

@Section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@Section('content')
@endsection

@Section('js')
@endsection