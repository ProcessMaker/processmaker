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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-all_requests" v-model="selectedPermissions">   {{__('View All Requests')}}</label>
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
            <label><input type="checkbox" name="view-scripts" :disabled="formData.is_administrator" value="view-scripts" v-model="selectedPermissions">   {{__('View Scripts')}}</label>
            <label><input type="checkbox" name="create-scripts" :disabled="formData.is_administrator" value="create-scripts" v-model="selectedPermissions" @change="checkCreate('edit-scripts', $event)">   {{__('Create Scripts')}}</label>
            <label><input type="checkbox" name="edit-scripts" :disabled="formData.is_administrator" value="edit-scripts" v-model="selectedPermissions" @change="checkEdit('create-scripts', $event)">   {{__('Edit Scripts')}}</label>
            <label><input type="checkbox" name="delete-scripts" :disabled="formData.is_administrator" value="delete-scripts" v-model="selectedPermissions">   {{__('Delete Scripts')}}</label>
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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-categories" v-model="selectedPermissions">   {{__('View Categories')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-categories" v-model="selectedPermissions" @change="checkCreate('edit-categories', $event)">   {{__('Create Categories')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-categories" v-model="selectedPermissions" @change="checkEdit('create-categories', $event)">   {{__('Edit Categories')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-categories" v-model="selectedPermissions">   {{__('Delete Categories')}}</label>
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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-screens" v-model="selectedPermissions">   {{__('View Screens')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-screens" v-model="selectedPermissions" @change="checkCreate('edit-screens', $event)">   {{__('Create Screens')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-screens" v-model="selectedPermissions" @change="checkEdit('create-screens', $event)">   {{__('Edit Screens')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-screens" v-model="selectedPermissions">   {{__('Delete Screens')}}</label>
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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-environment_variables" v-model="selectedPermissions">   {{__('View Environment Variables')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-environment_variables" v-model="selectedPermissions" @change="checkCreate('edit-environment_variables', $event)">   {{__('Create Environment Variables')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-environment_variables" v-model="selectedPermissions" @change="checkEdit('create-environment_variables', $event)">   {{__('Edit Environment Variables')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-environment_variables" v-model="selectedPermissions">   {{__('Delete Environment Variables')}}</label>
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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-users" v-model="selectedPermissions">   {{__('View Users')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-users" v-model="selectedPermissions" @change="checkCreate('edit-users', $event)">   {{__('Create Users')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-users" v-model="selectedPermissions" @change="checkEdit('create-users', $event)">   {{__('Edit Users')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-users" v-model="selectedPermissions">   {{__('Delete Users')}}</label>
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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-groups" v-model="selectedPermissions">   {{__('View Groups')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-groups" v-model="selectedPermissions" @change="checkCreate('edit-groups', $event)">   {{__('Create Groups')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-groups" v-model="selectedPermissions" @change="checkEdit('create-groups', $event)">   {{__('Edit Groups')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-groups" v-model="selectedPermissions">   {{__('Delete Groups')}}</label>
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
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-processes" v-model="selectedPermissions">   {{__('View Processes')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-processes" v-model="selectedPermissions" @change="checkCreate('edit-processes', $event)">   {{__('Create Processes')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-processes" v-model="selectedPermissions" @change="checkEdit('create-processes', $event)">   {{__('Edit Processes')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="archive-processes" v-model="selectedPermissions">   {{__('Archive Processes')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#comments">
                {{__('Comments')}}
            </button>
        </h5>
    </div>
    <div id="comments" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-comments" v-model="selectedPermissions">   {{__('View Comments')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-comments" v-model="selectedPermissions" @change="checkCreate('edit-comments', $event)">   {{__('Create Comments')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-comments" v-model="selectedPermissions" @change="checkEdit('create-comments', $event)">   {{__('Edit Comments')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-comments" v-model="selectedPermissions">   {{__('Delete Comments')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#files">
                {{__('Files (API)')}}
            </button>
        </h5>
    </div>
    <div id="files" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-files" v-model="selectedPermissions">   {{__('View Files')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-files" v-model="selectedPermissions">   {{__('Create Files')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-files" v-model="selectedPermissions">   {{__('Edit Files')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-files" v-model="selectedPermissions">   {{__('Delete Files')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#notifications">
                {{__('Notifications (API)')}}
            </button>
        </h5>
    </div>
    <div id="notifications" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-notifications" v-model="selectedPermissions">   {{__('View Notifications')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-notifications" v-model="selectedPermissions">   {{__('Create Notifications')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-notifications" v-model="selectedPermissions">   {{__('Edit Notifications')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-notifications" v-model="selectedPermissions">   {{__('Delete Notifications')}}</label>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#task_assignments">
                {{__('Task Assignments (API)')}}
            </button>
        </h5>
    </div>
    <div id="task_assignments" class="collapse" >
        <div class="card-body">
            <label><input type="checkbox" :disabled="formData.is_administrator" value="view-task_assignments" v-model="selectedPermissions">   {{__('View Task Assignments')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="create-task_assignments" v-model="selectedPermissions">   {{__('Create Task Assignments')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="edit-task_assignments" v-model="selectedPermissions">   {{__('Edit Task Assignments')}}</label>
            <label><input type="checkbox" :disabled="formData.is_administrator" value="delete-task_assignments" v-model="selectedPermissions">   {{__('Delete Task Assignments')}}</label>
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