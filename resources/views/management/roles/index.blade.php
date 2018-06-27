@extends('layouts.layout', ['title' => 'Role Management'])

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
    <div class="container page-content" v-cloak id="roles-listing">
        <!-- Role Add Dialog -->
          <b-modal ref="addModal" size="md" centered title="Create New Role">
      <form>
        <form-input v-model="addRoleCode" label="Code" type="email" name="code" :error="getFirstValidationError('code')"></form-input>
        <form-input v-model="addRoleName" label="Name" :error="getFirstValidationError('name')"></form-input>
        <form-input v-model="addRoleDescription" label="Description" :error="getFirstValidationError('description')"></form-input>
        <form-select v-model="addRoleStatus" label="Status" selected="DISABLED" :options="[{content: 'Active', value:'ACTIVE' },{content: 'Disabled', value:'DISABLED' }]"></form-select>
        <form-radiobutton-group v-model="test" label="ur mom" name="yeet" checked="DISABLED" :options="[{content: 'Active', value:'ACTIVE' },{content: 'Disabled', value:'DISABLED' }]"></form-radiobutton-group>
      </form>

    <template slot="modal-footer">
      <b-button @click="hideAddModal" class="btn-outline-secondary btn-md">
        Cancel
      </b-button>
      <b-button @click="submitAdd" class="btn-secondary text-light btn-md">
        Save
      </b-button>
    </template>

  </b-modal>
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">Roles</h1>
                <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <button @click="showAddModal" class="btn btn-secondary"><i class="fas fa-plus"></i> {{__('Role')}}</button>
                </div>
            </div>
            <roles-listing ref="rolesListing" :filter="filter"></roles-listing>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/management/roles/index.js')}}"></script>
@endsection
