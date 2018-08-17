@extends('layouts.layout', ['title' => 'Group Management'])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container page-content" v-cloak id="groups-listing">
        <!-- Group Add Dialog -->
          <b-modal ref="addModal" size="md" centered title="Create New Group">
      <form>
       <div class="form-group">
          <label for="add-group-title">Title</label>
          <input id="add-group-title" class="form-control" v-model="addGroupTitle">
        </div>
       <div class="form-group">
          <label for="add-group-status">Status</label>
          <select class="form-control" id="add-group-status" v-model="addGroupStatus">
              <option value="ACTIVE">Active</option>
              <option value="INACTIVE">Inactive</option>

          </select>
        </div>
   </form>

    <template slot="modal-footer">
      <b-button @click="hideAddModal" class="btn btn-outline-success btn-sm text-uppercase">
        Cancel
      </b-button>
      <b-button @click="submitAdd" class="btn btn-success btn-sm text-uppercase">
        Save
      </b-button>
    </template>

  </b-modal>
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center col-sm-12">
                <h1 class="page-title">Groups</h1>
                <input v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                    <button @click="showAddModal" class="btn btn-secondary"><i class="fas fa-plus"></i> {{__('Group')}}</button>
                </div>
            </div>
            <groups-listing ref="groupsListing" :filter="filter"></groups-listing>
        </div>
    </div>
    </div>
@endsection

@section('js')
<script src="{{mix('js/management/groups/index.js')}}"></script>
@endsection
