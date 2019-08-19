@foreach ($permissionGroups as $groupName => $permissions)
    <div class="card">
        <div class="card-header">
            <div class="mb-0">
                <button class="btn btn-link collapsed d-flex w-100 justify-content-between" type="button" data-toggle="collapse" data-target="#{{ \Illuminate\Support\Str::slug($groupName) }}">
                 <div>{{__($groupName)}}</div> <div><i class="fas fa-chevron-circle-down arrow-open mr-2"></i> <i class="fas fa-chevron-circle-left arrow-closed mr-2"></i> </div>  
                </button>
            </div>
        </div>
        <div id="{{ \Illuminate\Support\Str::slug($groupName) }}" class="collapse" >
            <div class="card-body">
                @foreach ($permissions as $permission)
                    <label><input type="checkbox"
                                  :disabled="formData.is_administrator"
                                  value="{{ $permission->name }}"
                                  v-model="selectedPermissions"
                                  @if ($permission->action == 'create')@@change="checkCreate('edit-{{ $permission->resource_name }}', $event)"@endif
                                  @if ($permission->action == 'edit')@@change="checkEdit('create-{{ $permission->resource_name }}', $event)"@endif
                            > {{__($permission->title)}}
                    </label>
                @endforeach
            </div>
        </div>
    </div>
@endforeach

@section('css')
    <style scoped>
        .card-body label {
            display: block;
        }
        .arrow-open {
            display:inline-block;
        }
        .arrow-closed {
            display:none;
        }
       .collapsed .arrow-open {
            display: none;
        }
       .collapsed .arrow-closed {
            display: inline-block;
        }
    </style>
@endsection