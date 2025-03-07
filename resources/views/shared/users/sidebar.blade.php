<div class="profile-sidebar">
    <div class="card card-body p-3">
        <h5 class="mb-3 font-weight-bold">{{__('Avatar')}}</h5>
        <div align="center" @click="openAvatarModal()">
            <avatar-image size="198"
                          :input-data="options" hide-name="true"></avatar-image>
        </div>
        <div class="w-100 mt-3">
            <div v-if="! formData.avatar">
                <button @click="openAvatarModal()" type="button" class="btn btn-secondary w-100">
                    <i class="fas fa-upload"></i> {{__('Upload Avatar')}}
                </button>
            </div>
            <div v-else class="d-flex">
                <button @click="openAvatarModal()" type="button" class="btn btn-outline-secondary w-50">
                    <i class="fas fa-edit"></i> {{__('Change')}}
                </button>
                <button type="button" @click="deleteAvatar" class="btn btn-outline-danger w-50 ml-3">
                    <i class="fas fa-times"></i> {{__('Clear')}}
                </button>
            </div>
        </div>
    </div>
    <div class="card card-body mt-3">
        <fieldset :disabled="{{ \Auth::user()->hasPermission('edit-user-and-password') || \Auth::user()->is_administrator ? 'false' : 'true' }}">
            <legend>
                <h5 class="mb-3 font-weight-bold">{{__('Login Information')}}</h5>
            </legend>
            <div class="form-group">
               {{ html()->label(__('Username') . '<small class="ml-1">*</small>', 'username') }}
               {{ html()->text('username')->id('username')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.username')->attribute('autocomplete', 'off')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.username}')->required()->attribute('aria-required', 'true') }}
               <div class="invalid-feedback" role="alert" v-if="errors.username">@{{errors.username[0]}}</div>
            </div>
            @if (config('password-policies.users_can_change', true) ||
              !Request::is('profile/edit') ||
              auth()->user()->is_administrator)
            @can('edit-user-and-password')
                <div class="form-group">
                    <small class="form-text text-muted">
                        {{__('Leave the password blank to keep the current password:')}}
                    </small>
                </div>
            @endcan
            <div class="form-group">
                {{ html()->label(__('New Password'), 'password') }}
                <vue-password v-model="formData.password" :disable-toggle=true>
                    <div slot="password-input" slot-scope="props">
                        {{ html()->password('password')->id('password')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.password')->attribute('autocomplete', 'new-password')->attribute('@input', 'props.updatePassword($event.target.value)')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.password}') }}
                    </div>
                </vue-password>
            </div>

            <div class="form-group">
                {{ html()->label(__('Confirm Password'), 'confPassword') }}
                {{ html()->password('confPassword')->id('confPassword')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.confPassword')->attribute('autocomplete', 'new-password')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.password}') }}
                <div class="invalid-feedback" :style="{display: (errors.password) ? 'block' : 'none' }" role="alert"
                     v-for="(error, index) in errors.password">@{{error}}</div>
            </div>

            @endif
            @cannot('edit-user-and-password')
                <div class="form-group">
                    <small class="form-text text-muted">
                        {{__('To change the current username and password please contact your administrator.')}}
                    </small>
                </div>
            @endcannot
        </fieldset>

        @if (!\Request::is('profile/edit'))
        <div class="form-group">
            {{ html()->label(__('User must change password at next login'), 'forceChangePassword') }}
            <div class="grouped">
                <div class="custom-control custom-switch">
                    <input v-model="formData.force_change_password" value="1" type="checkbox" class="custom-control-input" :id="'switch_force_change_password'">
                    <label class="custom-control-label" :for="'switch_force_change_password'"></label>
                </div>
            </div>
        </div>
        @endif

        @if (config('password-policies.2fa_enabled', false) && count($global2FAEnabled) > 0 && $is2FAEnabledForGroup)
            <div class="form-group">
                {{ html()->label(__('Two Factor Authentication'), 'preferences_2fa') }}
                <b-form-checkbox-group
                        id="preferences_2fa"
                        v-model="formData.preferences_2fa"
                        :options="global2FAEnabled"
                        :state="state2FA"
                        switches
                        required
                >
                </b-form-checkbox-group>
            </div>
        @endif
    </div>

    <div class="card card-body mt-3">
        <h5 class="mb-3 font-weight-bold">
            {{__('Task Notifications Email')}}
        </h5>
        <div class="form-group">
            <div class="custom-control custom-switch">
                {{ html()->checkbox('email_task_notification', 1, false)->id('email_task_notification')->class('custom-control-input')->attribute('v-model', 'formData.email_task_notification') }}
                <label class="custom-control-label" for="email_task_notification">{{__('Forward notifications to my email address')}}</label>
            </div>
        </div>
    </div>

    @isset($addons)
        @foreach ($addons as $addon)
            {!! $addon['content'] ?? '' !!}
        @endforeach
    @endisset

    <div class="card card-body mt-3">
        <h5 class="mb-3 font-weight-bold">{{__('Settings')}}</h5>
            <div class="form-group">
                {{ html()->label(__('Date Format'), 'datetime_format') }}
                <b-form-select id="datetime_format" v-model="formData.datetime_format" class="form-control" :options="datetimeFormats">
                </b-form-select>
                <div class="invalid-feedback" role="alert" v-if="errors.email">
                    @{{errors.datetime_format}}
                </div>
            </div>
            <div class="form-group">
                {{ html()->label(__('Time Zone'), 'timezone') }}
                <b-form-select id="timezone" v-model="formData.timezone" class="form-control" :options="timezones">
                </b-form-select>
                <div class="invalid-feedback" role="alert" v-if="errors.email">@{{errors.timezone}}
                </div>
            </div>

            <div class="form-group">
                {{ html()->label(__('Status'), 'status') }}
                <b-form-select id="status" v-model="formData.status" class="form-control" :options="status">
                </b-form-select>
                <div class="invalid-feedback" role="alert" v-if="errors.status">
                    @{{errors.status}}
                </div>
            </div>
            
            <div class="form-group">
                {{ html()->label(__('Recommendations'), 'status') }}
                <b-form-select
                    id="status"
                    v-model="disableRecommendations"
                    class="form-control"
                    :options="[{value: false, text: 'Enabled'}, {value: true, text: 'Disabled'}]">
                </b-form-select>
            </div>

            @isset($addonsSettings)
                @foreach ($addonsSettings as $addon)
                    {!! $addon['content'] ?? '' !!}
                @endforeach
            @endisset
    </div>
</div>
