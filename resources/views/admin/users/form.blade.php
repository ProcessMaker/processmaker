
@php 
    if ($user->exists){
        $route = ['users.update', $user->uuid_text ];
    } else {
        $route = ['users.store'];
    }
@endphp
{!! Form::model($user , ['route' => $route, 'id' => 'userForm']) !!}
    <div class="form-group">
        {!! Form::label('username', 'Username') !!}
        {!! Form::text('username', null, ['class'=> 'form-control']) !!}
        <small id="emailHelp" class="form-text text-muted">Username must be distinct</small>
    </div>
    <div class="form-group">
        {!! Form::label('firstname', 'First Name') !!}
        {!! Form::text('firstname', null, ['class'=> 'form-control']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('lastname', 'Last Name') !!}
        {!! Form::text('lastname', null, ['class'=> 'form-control']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('email', 'Email') !!}
        {!! Form::text('email', null, ['class'=> 'form-control']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('status', 'Status') !!}
        {!! Form::select('status', ['Active', 'Inactive'], null, ['class' => 'form-control']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('password', 'Password') !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('password_confirm', 'Confirm Password') !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>
    @if ($user->exists)
    <div class="d-flex justify-content-end mt-2">
        {!! Form::button('Cancel', ['class'=>'btn btn-outline-success']) !!}
        {!! Form::submit('Save', ['class'=>'btn btn-success ml-2']) !!}
    </div>
    @endif

{!! Form::close() !!}