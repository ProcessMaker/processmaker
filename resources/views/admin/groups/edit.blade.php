@extends('layouts.layout', ['title' => 'Edit Groups'])

@Section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@Section('content')

<div class="container">
      <h1 style="margin:15px">Edit Group</h1>
      <div class="row">
        <div class="col-8">
          <div class="card card-body">
            <form>
              <div class="form-group">
                <label for="groupName">Group Name</label>
                <input type="text" class="form-control" id="groupName">
              </div>
              <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" rows="3"></textarea>
              </div>
              <div class="form-group p-0">
                <label for="dropdownSelect">Status</label>
                <select class="form-control" id="dropdownSelect">
                  <option>Active</option>
                  <option>Inactive</option>
                  <option>Draft</option>
                </select>
              </div>
            </form>
            <div class="card-body text-right pr-0">
              <button type="button" class="btn btn-outline-success" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success ml-2">Save</button>
            </div>
        </div>
      </div>
        <div class="col-4">
          <div class="card card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@Section('js')
@endsection