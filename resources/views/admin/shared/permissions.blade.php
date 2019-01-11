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
            <label><input type="checkbox" value="view-all_requests" v-model="selected">   {{__('View All Requests')}}</label><br>
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
            <label><input type="checkbox" value="view-scripts" v-model="selected">   {{__('View Scripts')}}</label><br>
            <label><input type="checkbox" value="create-scripts" v-model="checked" ref="'scripts'">   {{__('Create Scripts')}}</label><br>
            <label><input type="checkbox" value="edit-scripts" v-model="checked" ref="'scripts'">   {{__('Edit Scripts')}}</label><br>
            <label><input type="checkbox" value="delete-scripts" v-model="selected">   {{__('Delete Scripts')}}</label><br>
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
            <label><input type="checkbox" value="view-categories" v-model="selected">   {{__('View Categories')}}</label><br>
            <label><input type="checkbox" value="create-categories" v-model="selected">   {{__('Create Categories')}}</label><br>
            <label><input type="checkbox" value="edit-categories" v-model="selected">   {{__('Edit Categories')}}</label><br>
            <label><input type="checkbox" value="delete-categories" v-model="selected">   {{__('Delete Categories')}}</label><br>
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
            <label><input type="checkbox" value="view-screens" v-model="selected">   {{__('View Screens')}}</label><br>
            <label><input type="checkbox" value="create-screens" v-model="selected">   {{__('Create Screens')}}</label><br>
            <label><input type="checkbox" value="edit-screens" v-model="selected">   {{__('Edit Screens')}}</label><br>
            <label><input type="checkbox" value="delete-screens" v-model="selected">   {{__('Delete Screens')}}</label><br>
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
            <label><input type="checkbox" value="view-environment_variables" v-model="selected">   {{__('View Environment Variables')}}</label><br>
            <label><input type="checkbox" value="create-environment_variables" v-model="selected">   {{__('Create Environment Variables')}}</label><br>
            <label><input type="checkbox" value="edit-environment_variables" v-model="selected">   {{__('Edit Environment Variables')}}</label><br>
            <label><input type="checkbox" value="delete-environment_variables" v-model="selected">   {{__('Delete Environment Variables')}}</label><br>
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
            <label><input type="checkbox" value="view-users" v-model="selected">   {{__('View Users')}}</label><br>
            <label><input type="checkbox" value="create-users" v-model="selected">   {{__('Create Users')}}</label><br>
            <label><input type="checkbox" value="edit-users" v-model="selected">   {{__('Edit Users')}}</label><br>
            <label><input type="checkbox" value="delete-users" v-model="selected">   {{__('Delete Users')}}</label><br>
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
            <label><input type="checkbox" value="view-groups" v-model="selected">   {{__('View Groups')}}</label><br>
            <label><input type="checkbox" value="create-groups" v-model="selected">   {{__('Create Groups')}}</label><br>
            <label><input type="checkbox" value="edit-groups" v-model="selected">   {{__('Edit Groups')}}</label><br>
            <label><input type="checkbox" value="delete-groups" v-model="selected">   {{__('Delete Groups')}}</label><br>
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
            <label><input type="checkbox" value="view-processes" v-model="selected">   {{__('View Processes')}}</label><br>
        </div>
    </div>
</div>