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
    <div class="container px-3" id="profileForm" v-cloak>
        <div class="d-flex flex-column flex-lg-row">
            <div class="flex-grow-1">
                @include('shared.users.profile')
            </div>
            <div class="ml-lg-3 mt-3 mt-lg-0">
                @include('shared.users.sidebar')
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
            {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'profileUpdate']) !!}
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="updateAvatarModal" ref="updateAvatarModal">
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
                    <div class="text-right">
                        <button type="button" @click="hideModal" class="btn btn-outline-secondary">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" @click="saveAvatar" class="btn btn-secondary ml-2">
                            {{__('Save')}}
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
        let formVueInstance = new Vue({
            el: '#profileForm',
            data: {
                formData: @json($currentUser),
                langs: @json($availableLangs),
                timezones: @json($timezones),
                datetimeFormats: @json($datetimeFormats),
                countries: @json($countries),
                states: @json($states),
                status: @json($status),
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
                profileUpdate() {
                    this.resetErrors();
                    if (!this.validatePassword()) return false;
                    if (this.image) {
                        this.formData.avatar = this.image;
                    }
                    if (this.image === false) {
                        this.formData.avatar = false;
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
                deleteAvatar() {
                    let optionValues = formVueInstance.$data.options[0];
                    optionValues.src = null;
                    formVueInstance.$data.options.splice(0, 1, optionValues)
                    formVueInstance.$data.image = false;
                    formVueInstance.$data.formData.avatar = false;
                    window.ProcessMaker.events.$emit('update-profile-avatar');
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
    
    <script>
        new Vue({
            el: '#updateAvatarModal',
            data() {
                return {
                    avatar: formVueInstance.$data.formData.avatar,
                    image: "",
                    idxx: window.ProcessMaker.user.id
                };
            },
            methods: {
                // Called when the croppie instance is completed
                cropResult() {
                },
                saveAvatar() {
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
                    this.$refs.updateAvatarModal.hidden = false;
                },
                hideModal() {
                    $('#updateAvatarModal').modal("hide")
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
@endsection
