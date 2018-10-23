@extends('layouts.layout')

@section('title')
{{__('Edit Profile')}}
@endsection

@section('content')
<div class="container" id="profileForm">
	<h1>{{__('Profile')}}</h1>
	<div class="row">
		<div class="col-8">
			<div class="card card-body">
				<h2>{{__('Name')}}</h2>
				<div class="row">
					<div class="form-group col">
						{!! Form::label('firstname', 'First Name') !!}
						{!! Form::text('firstname', null, ['id' => 'firstname','class'=> 'form-control', 'v-model' => 'firstname',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.firstname}']) !!}
						<div class="invalid-feedback" v-if="errors.firstname">@{{errors.firstname[0]}}</div>
					</div>
					<div class="form-group col">
						{!! Form::label('lastname', 'Last Name') !!}
						{!! Form::text('lastname', null, ['id' => 'lastname', 'rows' => 4, 'class'=> 'form-control', 'v-model'
						=> 'lastname', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.lastname}']) !!}
						<div class="invalid-feedback" v-if="errors.lastname">@{{errors.description[0]}}</div>
					</div>
				</div>
				<h2 class="mt-2">{{__('Contact Information')}}</h2>
				<div class="row">
					<div class="form-group col">
						{!! Form::label('email', 'Email') !!}
						{!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=> 'form-control', 'v-model'
						=> 'email', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.email}']) !!}
						<div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}</div>
					</div>
					<div class="form-group col">
						{!! Form::label('phone', 'Phone') !!}
						{!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control', 'v-model' => 'phone',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}']) !!}
						<div class="invalid-feedback" v-if="errors.phone">@{{errors.phone[0]}}</div>
					</div>
				</div>
				<h2 class="mt-2">{{__('Address')}}</h2>
				<div class="row">
					<div class="form-group col">
						{!! Form::label('address', 'Address') !!}
						{!! Form::text('address', null, ['id' => 'address','class'=> 'form-control', 'v-model' => 'address',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.address}']) !!}
						<div class="invalid-feedback" v-if="errors.address">@{{errors.address[0]}}</div>
					</div>
				</div>
				<div class="row">
					<div class="form-group col">
						{!! Form::label('city', 'City') !!}
						{!! Form::text('city', null, ['id' => 'city', 'rows' => 4, 'class'=> 'form-control', 'v-model'
						=> 'city', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.city}']) !!}
						<div class="invalid-feedback" v-if="errors.city">@{{errors.city[0]}}</div>
					</div>
					<div class="form-group col">
						{!! Form::label('state', 'State or Region') !!}
						{!! Form::select('state', ['L' => 'Large', 'S' => 'Small'], null, ['id' => 'state','class'=> 'form-control',
						'v-model' => 'state',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.state}']) !!}
						<div class="invalid-feedback" v-if="errors.state">@{{errors.state[0]}}</div>
					</div>
				</div>
				<div class="row">
					<div class="form-group col">
						{!! Form::label('code', 'Postal Code') !!}
						{!! Form::text('code', null, ['id' => 'code', 'rows' => 4, 'class'=> 'form-control', 'v-model'
						=> 'code', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.code}']) !!}
						<div class="invalid-feedback" v-if="errors.code">@{{errors.code[0]}}</div>
					</div>
					<div class="form-group col">
						{!! Form::label('country', 'Country') !!}
						{!! Form::select('country', ['L' => 'Large', 'S' => 'Small'], null, ['id' => 'country','class'=> 'form-control',
						'v-model' => 'country',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.country}']) !!}
						<div class="invalid-feedback" v-if="errors.country">@{{errors.country[0]}}</div>
					</div>
				</div>
				<h2 class="mt-2">{{__('Localization')}}</h2>
				<div class="row">
					<div class="form-group col">
						{!! Form::label('timezone', 'Timezone') !!}
						{!! Form::select('timezone', ['L' => 'Large', 'S' => 'Small'], null, ['id' => 'timezone','class'=>
						'form-control',
						'v-model' => 'timezone',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.timezone}']) !!}
						<div class="invalid-feedback" v-if="errors.timezone">@{{errors.timezone[0]}}</div>
					</div>
					<div class="form-group col">
						{!! Form::label('language', 'Language') !!}
						{!! Form::select('language', ['L' => 'Large', 'S' => 'Small'], null, ['id' => 'language','class'=>
						'form-control',
						'v-model' => 'language',
						'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.language}']) !!}
						<div class="invalid-feedback" v-if="errors.language">@{{errors.language[0]}}</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-4">
			<div class="card card-body">
				<div align="center" data-toggle="modal" data-target="#exampleModal">
					<img src="https://via.placeholder.com/150x150" alt="HEY" style="border-radius: 50%">
				</div>
				<div class="form-group">
					{!! Form::label('username', 'Username') !!}
					{!! Form::text('username', null, ['id' => 'username', 'rows' => 4, 'class'=> 'form-control', 'v-model'
					=> 'username', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.username}']) !!}
					<div class="invalid-feedback" v-if="errors.username">@{{errors.username[0]}}</div>
				</div>
				<div class="form-group">
					{!! Form::label('password', 'New Password') !!}
					{!! Form::password('password', ['id' => 'password', 'rows' => 4, 'class'=> 'form-control', 'v-model'
					=> 'password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
					<div class="invalid-feedback" v-if="errors.password">@{{errors.password[0]}}</div>
				</div>
				<div class="form-group">
					{!! Form::label('confpassword', 'Confirm confPassword') !!}
					{!! Form::password('confpassword', ['id' => 'confpassword', 'rows' => 4, 'class'=> 'form-control', 'v-model'
					=> 'confpassword', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.confpassword}']) !!}
					<div class="invalid-feedback" v-if="errors.confpassword">@{{errors.confpassword[0]}}</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal" tabindex="-1" role="dialog" id="exampleModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Modal body text goes here.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection

@section('js')
<script>
	new Vue({
		el: '#profileForm',
		data: {
			firstname: "",
			lastname: "",
			errors: {},
			email: "",
			phone: "",
			address: "",
			city: "",
			state: "",
			code: "",
			country: "",
			timezone: "",
			language: "",
			username: "",
			password: "",
			confpassword: ""
		}
	});
</script>
@endsection