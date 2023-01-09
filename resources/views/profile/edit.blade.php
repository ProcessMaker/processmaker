@extends('layouts.layout')

@section('title')
    {{__('Edit Profile')}}
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Profile') => route('profile.show', $currentUser->id),
        __('Edit') => null,
    ]])
@endsection

@section('content')
    <div id="profile-edit" :user-id="{{ \Auth::user()->id }}">
        <container v-cloak v-if="user">
            <container-page active link-text="Edit User Profile" :header="`Edit User Profile: ${user.fullname}`">
                <container-page header="General Information" icon="user">This is a test.</container-page>
                <container-page header="Login & Security" icon="lock">This is a security test.</container-page>
                <container-page header="Settings" icon="cog">This is a security test.</container-page>
                <container-page header="Avatar" icon="image">This is a security test.</container-page>
            </container-page>
            <container-page header="Administration">
                <container-page header="Groups" icon="users">This is a test.</container-page>
                <container-page header="Permissions" icon="tasks">This is a security test.</container-page>
                <container-page header="API Tokens" icon="key">This is a security test.</container-page>
                <container-page header="Audit Logs" icon="book">This is a security test.</container-page>
            </container-page>
        </container>
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection

@section('js')
	<script src="{{mix('js/admin/profile/edit.js')}}"></script>
    <script>
        new Vue({
            el: "#profile-edit",
            data() {
                return {
                  userId: @json($currentUser->id),
                  user: null,
                }
            },
            mounted() {
              this.retrieveUser();
            },
            methods: {
              retrieveUser() {
                ProcessMaker.apiClient.get('users/' + this.userId)
                    .then(response => {
                        this.user = response.data;
                    });
              }
            },
        })
    </script>
@endsection
