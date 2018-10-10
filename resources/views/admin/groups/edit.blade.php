@extends('layouts.layout', ['title' => 'Edit Groups'])

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')

<div class="container">
  <h1 style="margin:15px">Edit Group</h1>
  <div class="row">
    <div class="col-8">
      <div class="card card-body">
        {!! Form::model($group, ['route' => ['groups.update', $group->uuid_text ]]) !!}
        <div class="form-group">
          {!! Form::label('name', 'Group Name')!!}
          {!! Form::text('name', null, ['class'=> 'form-control']) !!}
        </div>
        <div class="form-group">
          {!! Form::label('description', 'Description') !!}
          {!! Form::textarea('description', null, ['class'=> 'form-control', 'rows' => 3]) !!}
        </div>
        <div class="form-group p-0">
          {!! Form::label('status', 'Status'); !!}
          {!! Form::select('status', ['Active', 'Inactive', 'Draft'], null, ['class' => 'form-control']) !!}
        </div>
        
        <div class="card-body text-right pr-0">
          {!! Form::button('Cancel', ['class'=>'btn btn-outline-success']) !!}
          {!! Form::submit('Save', ['class'=>'btn btn-success ml-2']) !!}
        </div>
        {!! Form::close() !!}
      </div>
    </div>
    <div class="col-4">
      <div class="card card-body">
      Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
    </div>
  </div>
</div>
@endsection

@section('js')
@endsection