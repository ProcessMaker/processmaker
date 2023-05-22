@extends('layouts.layout')

@section('title')
{{__('Profile')}}
@endsection

@section('breadcrumbs')
@include('shared.breadcrumbs', ['routes' => [
    __('Profile') => null,
]])
@endsection
@section('content')
<div class="container" id="profileForm">
    <div class="row">
        <div class="col-8">
            <div class="card card-body">
                <h4 class="mt-2">{{__('Contact Information')}}</h4>
                <table class="table">
                    @if($user->email)
                    <tr>
                        <td align="center"><i class="fas fa-envelope fa-lg text-secondary pr-1"></i></td>
                        <td>{{__('Email')}}</td>
                        <td width="100%"><a href="mailto:{{ ProcessMaker\SanitizeHelper::sanitizeEmail($user->email)}}">{{ ProcessMaker\SanitizeHelper::sanitizeEmail($user->email) }}</a></td>
                    </tr>
                    @endif
                    @if($user->phone)
                    <tr>
                        <td align="center"><i class="fas fa-phone fa-lg text-secondary pr-1"></i></td>
                        <td>{{__('Phone')}}</td>
                        <td><a href="{{'tel:' . ProcessMaker\SanitizeHelper::sanitizePhoneNumber($user->phone)}}">{{ ProcessMaker\SanitizeHelper::sanitizePhoneNumber($user->phone) }}</a></td>
                    </tr>
                    @endif
                    @if ($user->fax)
                    <tr>
                        <td align="center"><i class="fas fa-fax fa-lg text-secondary pr-1"></i></td>
                        <td>{{__('Fax')}}</td>
                        <td><a href="{{'tel:' . ProcessMaker\SanitizeHelper::sanitizePhoneNumber($user->fax)}}">{{ ProcessMaker\SanitizeHelper::sanitizePhoneNumber($user->fax) }}</a></td>
                    </tr>
                    @endif
                    @if ($user->cell)
                    <tr>
                        <td align="center"><i class="fas fa-mobile-alt fa-lg text-secondary pr-1"></i></td>
                        <td>{{__('Cell')}}</td>
                        <td><a href="{{'tel:' . ProcessMaker\SanitizeHelper::sanitizePhoneNumber($user->cell)}}">{{ ProcessMaker\SanitizeHelper::sanitizePhoneNumber($user->cell) }}</a></td>
                    </tr>
                    @endif


                    @if($user->address)
                    <tr>
                        <td colspan="3">
                            <h4 class="mb-0 mt-3">{{__('Address')}}</h4>
                        </td>
                    </tr>
                    <tr>
                        <!-- v-pre used to prevent xss by vue compilation  -->
                        <td colspan="3" v-pre>
                            {{ sanitizeVueExp($user->address) }}<br>
                            {{ sanitizeVueExp($user->city) }}, {{ sanitizeVueExp($user->state) }} {{ sanitizeVueExp($user->postal) }} {{ sanitizeVueExp($user->country) }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        <div class="col-4">
            <div class="card card-body">
                <div align="center">
                    <avatar-image size="150" :input-data="avatar"></avatar-image>
                    <h1 style="font-weight:100" v-pre>{{ sanitizeVueExp($user->firstname) }} {{ sanitizeVueExp($user->lastname) }}</h1>
                    <h4 v-pre>{{ sanitizeVueExp($user->title) }}</h4>
                    <hr>
                    <h5 class="mt-2" v-pre>{{__('Current Local Time')}}</h5>
                    <div><i class="far fa-calendar-alt fa-lg text-secondary pr-1"></i>
                        @{{moment().format() }}</div>
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
            user: @json($user)
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
        }
    });
</script>
@endsection
