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
    <div class="container" id="profileForm" v-cloak>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    <h2>{{__('Name')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('firstname', __('First Name')) !!}
                            {!! Form::text('firstname', null, ['id' => 'firstname','class'=> 'form-control', 'v-model' => 'formData.firstname',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.firstname}']) !!}
                            <div class="invalid-feedback" v-if="errors.firstname">@{{errors.firstname[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('lastname', __('Last Name')) !!}
                            {!! Form::text('lastname', null, ['id' => 'lastname', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formData.lastname', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.lastname}']) !!}
                            <div class="invalid-feedback" v-if="errors.lastname">@{{errors.lastname[0]}}</div>
                        </div>
                    </div>
                    <h2 class="mt-2">{{__('Contact Information')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('title', __('Job Title')) !!}
                            {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'formData.title',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.title}']) !!}
                            <div class="invalid-feedback" v-if="errors.title">@{{errors.title}}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('email', __('Email')) !!}
                            {!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formData.email', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.email}']) !!}
                            <div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('phone', __('Phone')) !!}
                            {!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control', 'v-model' => 'formData.phone',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}']) !!}
                            <div class="invalid-feedback" v-if="errors.phone">@{{errors.phone}}</div>
                        </div>
                    </div>
                    <h2 class="mt-2">{{__('Address')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('address', __('Address')) !!}
                            {!! Form::text('address', null, ['id' => 'address','class'=> 'form-control', 'v-model' => 'formData.address',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.address}']) !!}
                            <div class="invalid-feedback" v-if="errors.address">@{{errors.address}}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('city', __('City')) !!}
                            {!! Form::text('city', null, ['id' => 'city', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formData.city', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.city}']) !!}
                            <div class="invalid-feedback" v-if="errors.city">@{{errors.city}}</div>
                        </div>
                        <div class="form-group col" v-show="formData.country === 'US'">
                            {!! Form::label('state', __('State or Region')) !!}
                            {!! Form::select('state',
                                    $states,
                                    'formData.state',
                                    ['id' => 'state',
                                        'class'=> 'form-control',
                                        'v-model' => 'formData.state',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.state}'
                                    ])
                             !!}
                            <div class="invalid-feedback" v-if="errors.state">@{{errors.state}}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('postal', __('Postal Code')) !!}
                            {!! Form::text('postal', null, ['id' => 'postal', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formData.postal', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.postal}']) !!}
                            <div class="invalid-feedback" v-if="errors.postal">@{{errors.postal}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('country', __('Country')) !!}
                             <b-form-select v-model="formData.country" :options="countries" placeholder="Select" class="form-control">
                                <template slot="first">
                                    <option :value="null" disabled>{{__('Select')}}</option>
                                </template>
                             </b-form-select>
                            <div class="invalid-feedback" v-if="errors.country">@{{errors.country}}</div>
                        </div>
                    </div>
                    <h2 class="mt-2">{{__('Localization')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!!Form::label('datetime_format', __('Date Format'));!!}
                            {!!Form::select('datetime_format',
                                $datetimeFormats,
                                'formData.datetime_format',
                                ['id' => 'datetime_format',
                                        'class' => 'form-control',
                                        'v-model' => 'formData.datetime_format',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.datetime_format}'
                                ])
                            !!}
                            <div class="invalid-feedback" v-if="errors.email">@{{errors.datetime_format}}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col">
                            {!!Form::label('timezone', __('Time Zone'));!!}
                            {!!Form::select('timezone',
                                $timezones,
                                'formData.timezone',
                                 ['id'=>'timezone',
                                    'class'=> 'form-control',
                                    'v-model'=> 'formData.timezone',
                                    'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.datetimeFormat}'
                                 ])
                             !!}
                            <div class="invalid-feedback" v-if="errors.email">@{{errors.timezone}}</div>
                        </div>

                        <div class="form-group col" v-if="langs.length > 1">
                            {!! Form::label('language', __('Language')) !!}
                            <b-form-select v-model="formData.language" :options="langs"></b-form-select>
                            <div class="invalid-feedback" v-if="errors.language">@{{errors.language}}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card card-body">

                    <div align="center" data-toggle="modal" data-target="#exampleModal">
                        <avatar-image size="150" class-image="m-1"
                                      :input-data="options"></avatar-image>
                    </div>
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
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="exampleModal" ref="exampleModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Upload Avatar')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div>
                        <div v-if="!image" class="no-avatar" align="center">{{__('Click the browse button below to get started')}}</div>
                        <div align="center">
                            <button type="button" @click="browse" class="btn btn-secondary mt-5 mb-2" ><i class="fas fa-upload"></i>
                                {{__('Browse')}}
                            </button>
                        </div>
                        <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie"
                                     :viewport="{ width: 380, height: 380, type: 'circle' }"
                                     :boundary="{ width: 400, height: 400 }"
                                     :enable-orientation="false" :enable-resize="false">
                        </vue-croppie>
                    </div>
                    <input type="file" class="custom-file-input" ref="customFile" @change="onFileChange">
                </div>

                <div class="modal-footer">
                    <div>
                        <button type="button" @click="hideModal" class="btn btn-outline-secondary">
                            {{__('Cancel')}}
                        </button>

                        <button type="button" @click="saveAndEmit" class="btn btn-secondary ml-2">
                            {{__('Continue')}}
                        </button>
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
	<script src="{{mix('js/admin/profile/edit.js')}}"></script>

    <script>
        new Vue({
            el: '#exampleModal',
            data() {
                return {
                    image: "",
                    idxx: window.ProcessMaker.user.id
                };
            },
            methods: {
                // Called when the croppie instance is completed
                cropResult() {
                },
                saveAndEmit() {
                    // We will close our modal, but we will ALSO emit a message stating the image has been updated
                    // The parent component will listen for that message and update it's data to reflect the new image
                    this.$refs.croppie.result({}, (selectedImage) => {
                        // Update the profile's avatar image with the selected one
                        let optionValues = formVueInstance.$data.options[0];
                        optionValues.src = selectedImage;
                        formVueInstance.$data.options.splice(0, 1, optionValues)
                        formVueInstance.$data.formData.avatar = selectedImage;
                        formVueInstance.$data.image = selectedImage;

                        // And finally close the modal
                        this.hideModal();
                    })
                },
                browse() {
                    this.$refs.customFile.click();
                },
                openModal() {
                    this.$refs.exampleModal.hidden = false;
                },
                hideModal() {
                    $('#exampleModal').modal("hide")
                },
                onFileChange(e) {
                    let files = e.target.files || e.dataTransfer.files;
                    if (!files.length) return;
                    this.createImage(files[0]);
                },
                createImage(file) {
                    let reader = new FileReader();

                    // Assigning the load listener to store the contents of the file to our image property
                    reader.onload = e => {
                        // Show we now have an image in our modal to use
                        this.image = true;
                        this.$refs.croppie.bind({
                            url: e.target.result
                        });
                    };
                    // Now actually read it, calling the onload after it's read
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>

    <script>
        let formVueInstance = new Vue({
            el: '#profileForm',
            data: {
                formData: @json($currentUser),
                langs: @json($availableLangs),
                countries: @json($countries),
                errors: {
                    username: null,
                    firstname: null,
                    lastname: null,
                    email: null,
                    password: null,
                    status: null
                },
				confPassword: '',
                image: '',
                options: [
                    {
                        src: @json($currentUser['avatar']),
                        title: @json($currentUser['fullname']),
                        initials: @json($currentUser['firstname'][0]) + @json($currentUser['lastname'][0])
                    }
                ]
            },
            methods: {
                onUpdate() {
                    this.resetErrors();
                    if (!this.validatePassword()) return false;
                    if (this.image) {
                        this.formData.avatar = this.image;
                    }
                    ProcessMaker.apiClient.put('users/' + this.formData.id, this.formData)
                        .then((response) => {
                            ProcessMaker.alert(this.$t('Your profile was saved.'), 'success')
                            window.ProcessMaker.events.$emit('update-profile-avatar');
                        })
                        .catch(error => {
                            this.errors = error.response.data.errors;
                        });
                },
                onClose() {

                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        username: null,
                        firstname: null,
                        lastname: null,
                        email: null,
                        password: null,
                        status: null
                    });
                },
                validatePassword() {
                    if (!this.formData.password && !this.formData.confPassword) {
                        delete this.formData.password;
                        return true;
                    }
                    if (this.formData.password.trim() === '' && this.formData.confPassword.trim() === '') {
                        delete this.formData.password;
                        return true
                    }
					if (this.formData.password.trim().length > 0 && this.formData.password.trim().length < 8) {
						this.errors.password = ['Password must be at least 8 characters']
                        this.password = ''
                        this.submitted = false
                        return false
					}
                    if (this.formData.password !== this.formData.confPassword) {
                        this.errors.password = ['Passwords must match']
                        this.password = ''
                        this.submitted = false
                        return false
                    }
                    return true
                },
            }
        });
    </script>
@endsection
