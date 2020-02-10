<div class="profile-sidebar">
    <div class="card card-body p-3">
        <h5 class="mb-3 font-weight-bold">{{__('Avatar')}}</h5>
        <div align="center" data-toggle="modal" data-target="#updateAvatarModal">
            <avatar-image size="198" class-image="m-1"
                          :input-data="options" hide-name="true"></avatar-image>
        </div>
        <div class="w-100 mt-3">
            <div v-if="! formData.avatar">
                <button data-toggle="modal" data-target="#updateAvatarModal" type="button" class="btn btn-secondary w-100">
                    <i class="fas fa-upload"></i> {{__('Upload Avatar')}}
                </button>
            </div>
            <div v-else class="d-flex">
                <button data-toggle="modal" data-target="#updateAvatarModal" type="button" class="btn btn-outline-secondary w-50">
                    <i class="fas fa-edit"></i> {{__('Change')}}
                </button>
                <button type="button" @click="deleteAvatar" class="btn btn-outline-danger w-50 ml-3">
                    <i class="fas fa-times"></i> {{__('Clear')}}
                </button>
            </div>
        </div>
    </div>
    <div class="card card-body mt-3">
        <h5 class="mb-3 font-weight-bold">{{__('Login Information')}}</h5>
        <div class="form-group">
            {!! Form::label('username', __('Username')) !!}
            {!! Form::text('username', null, ['id' => 'username', 'rows' => 4, 'class'=> 'form-control', 'v-model'
            => 'formData.username', 'autocomplete' => 'off', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.username}']) !!}
            <div class="invalid-feedback" v-if="errors.username">@{{errors.username[0]}}</div>
        </div>
        <div class="form-group">
            <small class="form-text text-muted">
                {{__('Leave the password blank to keep the current password:')}}
            </small>
        </div>
        <div class="form-group">
            {!! Form::label('password', __('New Password')) !!}
            <vue-password v-model="formData.password" :disable-toggle=true>
                <div slot="password-input" slot-scope="props">
                    {!! Form::password('password', ['id' => 'password', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                    => 'formData.password', 'autocomplete' => 'new-password', '@input' => 'props.updatePassword($event.target.value)',
                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
                </div>
            </vue-password>
        </div>
        <div class="form-group">
            {!! Form::label('confPassword', __('Confirm Password')) !!}
            {!! Form::password('confPassword', ['id' => 'confPassword', 'rows' => 4, 'class'=> 'form-control', 'v-model'
            => 'formData.confPassword', 'autocomplete' => 'new-password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
            <div class="invalid-feedback" :style="{display: (errors.password) ? 'block' : 'none' }" v-if="errors.password">@{{errors.password[0]}}</div>
        </div>
    </div>
    <div class="card card-body mt-3">
        <h5 class="mb-3 font-weight-bold">{{__('Settings')}}</h5>
            <div class="form-group">
                {!!Form::label('datetime_format', __('Date Format'));!!}
                <b-form-select id="datetime_format" v-model="formData.datetime_format" class="form-control" :options="datetimeFormats">
                </b-form-select>
                <div class="invalid-feedback" v-if="errors.email">
                    @{{errors.datetime_format}}
                </div>
            </div>
            <div class="form-group">
                {!!Form::label('timezone', __('Time Zone'));!!}
                <b-form-select id="timezone" v-model="formData.timezone" class="form-control" :options="timezones">
                </b-form-select>
                <div class="invalid-feedback" v-if="errors.email">@{{errors.timezone}}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('language', __('Language')) !!}
                <b-form-select id="language" v-model="formData.language" class="form-control" :options="langs">
                </b-form-select>
                <div class="invalid-feedback" v-if="errors.language">
                    @{{errors.language}}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('status', __('Status')) !!}
                <b-form-select id="status" v-model="formData.status" class="form-control" :options="status">
                </b-form-select>
                <div class="invalid-feedback" v-if="errors.status">
                    @{{errors.status}}
                </div>
            </div>
    </div>
    @isset($addons)
        @foreach ($addons as $addon)
            {!! __($addon['content']) !!}
        @endforeach
    @endisset
</div>
