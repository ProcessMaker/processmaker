 @foreach($groupedPermissions as $groupTitle => $permissions)
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#{{camel_case($groupTitle)}}">
                    {{ $groupTitle }}
                </button>
            </h5>
        </div>
        <div id="{{camel_case($groupTitle)}}" class="collapse" >
            <div class="card-body">
                @foreach ($permissions as $permission)
                <div><input type="checkbox" name="{{$groupTitle}}" value="{{$permission->id}}" v-model="selected">  {{ $permission->title }}</div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach