@extends('layouts.layout')

@section('title')
    {{__('Edit Profile')}}
@endsection

@section('content')
    <div class="container" id="profileForm">
        <h1>{{__('Profile')}}</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    <h2>{{__('Name')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('firstname', 'First Name') !!}
                            {!! Form::text('firstname', null, ['id' => 'firstname','class'=> 'form-control', 'v-model' => 'formdata.firstname',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.firstname}']) !!}
                            <div class="invalid-feedback" v-if="errors.firstname">@{{errors.firstname[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('lastname', 'Last Name') !!}
                            {!! Form::text('lastname', null, ['id' => 'lastname', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formdata.lastname', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.lastname}']) !!}
                            <div class="invalid-feedback" v-if="errors.lastname">@{{errors.description[0]}}</div>
                        </div>
                    </div>
                    <h2 class="mt-2">{{__('Contact Information')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('email', 'Email') !!}
                            {!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formdata.email', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.email}']) !!}
                            <div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('phone', 'Phone') !!}
                            {!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control', 'v-model' => 'formdata.phone',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}']) !!}
                            <div class="invalid-feedback" v-if="errors.phone">@{{errors.phone[0]}}</div>
                        </div>
                    </div>
                    <h2 class="mt-2">{{__('Address')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('address', 'Address') !!}
                            {!! Form::text('address', null, ['id' => 'address','class'=> 'form-control', 'v-model' => 'formdata.address',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.address}']) !!}
                            <div class="invalid-feedback" v-if="errors.address">@{{errors.address[0]}}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('city', 'City') !!}
                            {!! Form::text('city', null, ['id' => 'city', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formdata.city', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.city}']) !!}
                            <div class="invalid-feedback" v-if="errors.city">@{{errors.city[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('state', 'State or Region') !!}
                            {!! Form::select('state', [$current_user->state=>$current_user->state, $states], $current_user->state, ['id' => 'state','class'=> 'form-control',
                            'v-model' => 'formdata.state',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.state}']) !!}
                            <div class="invalid-feedback" v-if="errors.state">@{{errors.state[0]}}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('postal', 'Postal code') !!}
                            {!! Form::text('postal', null, ['id' => 'postal', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formdata.postal', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.postal}']) !!}
                            <div class="invalid-feedback" v-if="errors.postal">@{{errors.postal[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('country', 'Country') !!}
                            {!! Form::select('country', [$current_user->country=>$current_user->country, $countries], $current_user->country, ['id' => 'country','class'=> 'form-control',
                            'v-model' => 'formdata.country',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.country}']) !!}
                            <div class="invalid-feedback" v-if="errors.country">@{{errors.country[0]}}</div>
                        </div>
                    </div>
                    <h2 class="mt-2">{{__('Localization')}}</h2>
                    <div class="row">
                        <div class="form-group col">
                            {!! Form::label('timezone', 'Timezone') !!}
                            {!! Form::select('timezone', [$current_user->timezone=>$current_user->timezone, $timezones], $current_user->timezone, ['id' => 'timezone','class'=>
                            'form-control',
                            'v-model' => 'formdata.timezone',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.timezone}']) !!}
                            <div class="invalid-feedback" v-if="errors.timezone">@{{errors.timezone[0]}}</div>
                        </div>
                        <div class="form-group col">
                            {!! Form::label('language', 'Language') !!}
                            {!! Form::select('language', [$current_user->language=>$current_user->language], $current_user->language, ['id' => 'language','class'=>
                            'form-control',
                            'v-model' => 'formdata.language',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.language}']) !!}
                            <div class="invalid-feedback" v-if="errors.language">@{{errors.language[0]}}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card card-body">
                    <div align="center" data-toggle="modal" data-target="#exampleModal">
                        <avatar-image class-container="d-flex" size="150" class-image="m-1"
                                      :input-data="options"></avatar-image>
                    </div>
                    <div class="form-group">
                        {!! Form::label('username', 'Username') !!}
                        {!! Form::text('username', null, ['id' => 'username', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                        => 'formdata.username', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.username}']) !!}
                        <div class="invalid-feedback" v-if="errors.username">@{{errors.username[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('password', 'New Password') !!}
                        {!! Form::password('password', ['id' => 'password', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                        => 'formdata.password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
                        <div class="invalid-feedback" v-if="errors.password">@{{errors.password[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('confpassword', 'Confirm confPassword') !!}
                        {!! Form::password('confpassword', ['id' => 'confpassword', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                        => 'formdata.confpassword', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.confpassword}']) !!}
                        <div class="invalid-feedback" v-if="errors.confpassword">@{{errors.confpassword[0]}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" tabindex="-1" role="dialog" id="exampleModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div>
                        <div v-if="!image" class="no-avatar">Click the browse button below to get started</div>
                        <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie"
                                     :viewport="{ width: 380, height: 380, type: 'circle' }"
                                     :boundary="{ width: 400, height: 400 }"
                                     :enableOrientation="false" :enableResize="false">
                        </vue-croppie>
                    </div>
                    <input type="file" class="custom-file-input" ref="customFile" @change="onFileChange">
                </div>

                <div class="modal-footer">
                    <b-button @click="browse" class="btn btn-success btn-sm text-uppercase"><i class="fas fa-upload"></i>
                        BROWSE
                    </b-button>

                    <b-button @click="hideModal" class="btn btn-outline-success btn-md">
                        CANCEL
                    </b-button>

                    <b-button @click="saveAndEmit" class="btn btn-success btn-sm text-uppercase">
                        CONTINUE
                    </b-button>

                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
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
            el: '#exampleModal',
            data() {
                return {
                    image: "",
                    id: window.ProcessMaker.user.id
                };
            },
            methods: {
                // Called when the croppie instance is completed
                cropResult() {
                },
                saveAndEmit() {
                    // We will close our modal, but we will ALSO emit a message stating the image has been updated
                    // The parent component will listen for that message and update it's data to reflect the new image
                    this.$refs.croppie.result({}, (output) => {
                        this.$emit('image-update', output);

                        // And finally close the modal
                        this.hideModal();

                    })

                },
                browse() {
                    this.$refs.customFile.click();
                },
                openModal() {
                    this.$refs.profileModal.show();
                },
                hideModal() {
                    this.$refs.profileModal.hide();
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
        new Vue({
            el: '#profileForm',
            data: {
                formdata: @json($current_user),
                errors: {},
				confpassword: "",
                options: [
                    {
                        src: @json($current_user['avatar']),
                        title: @json($current_user['fullname']),
                        initials: @json($current_user['firstname'][0]) + @json($current_user['lastname'][0])
                    }
                ]
            }
        });
    </script>
@endsection