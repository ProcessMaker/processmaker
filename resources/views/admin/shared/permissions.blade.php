<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#requests">
                {{__('Requests')}}
            </button>
        </h5>
    </div>
    <div id="requests" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-all_requests" v-model="selectedPermissions">   {{__('View All Requests')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#scripts">
                {{__('Scripts')}}
            </button>
        </h5>
    </div>
    <div id="scripts" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" name="view-scripts" value="view-scripts" v-model="selectedPermissions">   {{__('View Scripts')}}</label>
            <label><input type="checkbox" name="create-scripts" value="create-scripts" v-model="selectedPermissions" @change="checkCreate('edit-scripts', $event)">   {{__('Create Scripts')}}</label>
            <label><input type="checkbox" name="edit-scripts" value="edit-scripts" v-model="selectedPermissions" @change="checkEdit('create-scripts', $event)">   {{__('Edit Scripts')}}</label>
            <label><input type="checkbox" name="delete-scripts" value="delete-scripts" v-model="selectedPermissions">   {{__('Delete Scripts')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#categories">
                {{__('Categories')}}
            </button>
        </h5>
    </div>
    <div id="categories" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-categories" v-model="selectedPermissions">   {{__('View Categories')}}</label>
            <label><input type="checkbox" value="create-categories" v-model="selectedPermissions" @change="checkCreate('edit-categories', $event)">   {{__('Create Categories')}}</label>
            <label><input type="checkbox" value="edit-categories" v-model="selectedPermissions" @change="checkEdit('create-categories', $event)">   {{__('Edit Categories')}}</label>
            <label><input type="checkbox" value="delete-categories" v-model="selectedPermissions">   {{__('Delete Categories')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#screens">
                {{__('Screens')}}
            </button>
        </h5>
    </div>
    <div id="screens" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-screens" v-model="selectedPermissions">   {{__('View Screens')}}</label>
            <label><input type="checkbox" value="create-screens" v-model="selectedPermissions" @change="checkCreate('edit-screens', $event)">   {{__('Create Screens')}}</label>
            <label><input type="checkbox" value="edit-screens" v-model="selectedPermissions" @change="checkEdit('create-screens', $event)">   {{__('Edit Screens')}}</label>
            <label><input type="checkbox" value="delete-screens" v-model="selectedPermissions">   {{__('Delete Screens')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#environment_variables">
                {{__('Environment Variables')}}
            </button>
        </h5>
    </div>
    <div id="environment_variables" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-environment_variables" v-model="selectedPermissions">   {{__('View Environment Variables')}}</label>
            <label><input type="checkbox" value="create-environment_variables" v-model="selectedPermissions" @change="checkCreate('edit-environment_variables', $event)">   {{__('Create Environment Variables')}}</label>
            <label><input type="checkbox" value="edit-environment_variables" v-model="selectedPermissions" @change="checkEdit('create-environment_variables', $event)">   {{__('Edit Environment Variables')}}</label>
            <label><input type="checkbox" value="delete-environment_variables" v-model="selectedPermissions">   {{__('Delete Environment Variables')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#users">
                {{__('Users')}}
            </button>
        </h5>
    </div>
    <div id="users" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-users" v-model="selectedPermissions">   {{__('View Users')}}</label>
            <label><input type="checkbox" value="create-users" v-model="selectedPermissions" @change="checkCreate('edit-users', $event)">   {{__('Create Users')}}</label>
            <label><input type="checkbox" value="edit-users" v-model="selectedPermissions" @change="checkEdit('create-users', $event)">   {{__('Edit Users')}}</label>
            <label><input type="checkbox" value="delete-users" v-model="selectedPermissions">   {{__('Delete Users')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#groups">
                {{__('Groups')}}
            </button>
        </h5>
    </div>
    <div id="groups" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-groups" v-model="selectedPermissions">   {{__('View Groups')}}</label>
            <label><input type="checkbox" value="create-groups" v-model="selectedPermissions" @change="checkCreate('edit-groups', $event)">   {{__('Create Groups')}}</label>
            <label><input type="checkbox" value="edit-groups" v-model="selectedPermissions" @change="checkEdit('create-groups', $event)">   {{__('Edit Groups')}}</label>
            <label><input type="checkbox" value="delete-groups" v-model="selectedPermissions">   {{__('Delete Groups')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#processes">
                {{__('Processes')}}
            </button>
        </h5>
    </div>
    <div id="processes" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" value="view-processes" v-model="selectedPermissions">   {{__('View Processes')}}</label>
        </div>
    </div>
</div>

@section('css')
    <style scoped>
        .card-body label {
            display: block;
        }
    </style>
@endsection