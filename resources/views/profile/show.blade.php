@extends('layouts.layout')

@section('title')
{{__('Profile')}}
@endsection

@section('content')
<div class="container" id="profileForm">
    <h1>{{__('Profile')}}</h1>
    <div class="row">
        <div class="col-8">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab"
                        aria-controls="nav-home" aria-selected="true">Information</a>
                    <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab"
                        aria-controls="nav-profile" aria-selected="false">Permissions</a>
                    <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab"
                        aria-controls="nav-contact" aria-selected="false">Groups</a>
                </div>
            </nav>
            <div class="card card-body tab-content mt-3" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                    <h4 class="mt-2">{{__('Contact Information')}}</h4>
                    <table class="table">
                        <tr>
                            <td align="center"><i class="fas fa-envelope fa-lg text-secondary pr-1"></i></td>
                            <td>{{__('Email')}}</td>
                            <td width="100%"><a href="mailto:{{$user->email}}">{{$user->email}}</a></td>
                        </tr>
                        <tr>
                            <td align="center"><i class="fas fa-phone fa-lg text-secondary pr-1"></i></td>
                            <td>{{__('Phone')}}</td>
                            <td><a href="{{'tel:' . $user->phone}}">{{$user->phone}}</a></td>
                        </tr>
                        @if ($user->fax)
                        <tr>
                            <td align="center"><i class="fas fa-fax fa-lg text-secondary pr-1"></i></td>
                            <td>{{__('Fax')}}</td>
                            <td><a href="{{'tel:' . $user->fax}}">{{$user->fax}}</a></td>
                        </tr>
                        @endif
                        @if ($user->cell)
                        <tr>
                            <td align="center"><i class="fas fa-mobile-alt fa-lg text-secondary pr-1"></i></td>
                            <td>{{__('Cell')}}</td>
                            <td><a href="{{'tel:' . $user->cell}}">{{$user->cell}}</a></td>
                        </tr>
                        @endif


                        @if($user->address)
                        <tr>
                            <td colspan="3">
                                <h4 class="mb-0 mt-3">{{__('Address')}}</h4>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                {{$user->address}}<br>
                                {{$user->city}}, {{$user->state}} {{$user->postal}} {{$user->country}}
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                    <table class="table mb-0">
                          <thead>
                            <tr>
                                <th></th>
                                <th><input type="checkbox" v-model="selectAll" @click="select"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="permission in permissions">
                                <td>@{{permission.name}}<br>@{{permission.description}}</td>
                                <td><input type="checkbox" :value="permission.id" v-model="selected"></td>
                            </tr>
                        </tbody>
                    </table>
                    <hr class="mt-0">
                    <button class="btn btn-secondary float-right" @click="onUpdate">SUBMIT</button>
                </div>
                <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">...vvv</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card card-body">
                <div align="center">
                    <avatar-image size="150" :input-data="avatar"></avatar-image>
                    <h1>{{$user->firstname}} {{$user->lastname}}</h1>
                    <h4>{{$user->title}}</h4>
                    <hr>
                    <h5 class="mt-2">{{__('Current Local Time')}}</h5>
                    <div><i class="far fa-calendar-alt fa-lg text-secondary pr-1"></i>{{
                        Carbon\Carbon::now()->setTimezone($user->timezone)->format(config('app.dateformat')) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection

@section('js')
<script>
    new Vue({
        el: '#profileForm',
        data: {
            user: @json($user),
            permissions: @json($all_permissions),
            selected: [],
		    selectAll: false
        },
        computed: {
            avatar() {
                return [{
                    src: this.user.avatar,
                    title: this.user.username,
                    name: '',
                    initials: this.user.firstname.match(/./u)[0] + this.user.lastname.match(/./u)[0]
                }];
            }
        },
        methods: {
            select() {
                this.selected = [];
                if (!this.selectAll) {
                    for (let permission in this.permissions) {
                        this.selected.push(this.permissions[permission].id);
                    }
                }
            },
            onUpdate() {
                ProcessMaker.apiClient.put("/permissions", {
                    permissionIds: this.selected
                    })
                .then(response => {
                    ProcessMaker.alert('{{__('Permission successfully added ')}}', 'success');
                })
            },
        }
    });
</script>
@endsection