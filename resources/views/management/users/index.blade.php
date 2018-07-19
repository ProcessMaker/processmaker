@extends('layouts.layout', ['title' => 'User Management'])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" id="users-listing" v-cloak>
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">Users</h1>
                <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <a @click="openAddUserModal" href="#" class="btn btn-action"><i class="fas fa-plus"></i> {{__('User')}}</a>
                </div>
            </div>
            <users-listing ref="listing" :filter="filter"></users-listing>
        </div>
    </div>
    <b-modal ref="addModal" size="md" centered title="Create New User" v-cloak>
        <form-input v-model="addUser.username" label="Username" helper="Username must be distinct"></form-input>
        <form-input v-model="addUser.firstname" label="First Name"></form-input>
        <form-input v-model="addUser.lastname" label="Last Name"></form-input>
        <form-input type="password" v-model="addUser.password" label="Password"></form-input>
        <form-input type="password" v-model="passwordConfirmation" label="Confirm Password"></form-input>

        <template slot="modal-footer">
        <b-button @click="hideAddModal" class="btn-outline-secondary btn-md">
            Cancel
        </b-button>
        <b-button @click="submitAdd" class="btn-secondary text-light btn-md">
            Save
        </b-button>
        </template>
    </b-modal>
</div>
@endsection

@section('js')
<script src="{{mix('js/management/users/index.js')}}"></script>
@endsection
