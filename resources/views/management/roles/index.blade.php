@extends('layouts.layout', ['title' => 'Role Management'])

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" v-cloak id="roles-listing">
        <!-- Role Add Dialog -->
          <b-modal ref="addModal" size="md" centered title="Create New Role">
      <form>
        <div class="form-group">
          <label for="add-role-code">Code</label>
          <input id="add-role-code" class="form-control" v-model="addRoleCode">
        </div>
        <div class="form-group">
          <label for="add-role-name">Name</label>
          <input id="add-role-name" class="form-control" v-model="addRoleName">
        </div>
        <div class="form-group">
          <label for="add-role-name">Description</label>
          <input id="add-role-name" class="form-control" v-model="addRoleDescription">
        </div>
        <div class="form-group">
          <label for="add-role-status">Status</label>
          <select class="form-control" id="add-role-status" v-model="addRoleStatus">
              <option value="ACTIVE">Active</option>
              <option value="DISABLED">Disabled</option>

          </select>
        </div>
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
                    <button @click="showAddModal" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Role')}}</button>
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
