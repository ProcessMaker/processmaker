@extends('layouts.layout')

@section('title')
    {{__('Edit Users')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Users') => route('users.index'),
        __('Edit') . " " . $user->fullname => null,
    ]])
    <div class="container" id="editUser">
        <div class="row">
            <div class="col-12">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home"
                           role="tab"
                           aria-controls="nav-home" aria-selected="true">{{__('Information')}}</a>
                        <a class="nav-item nav-link" id="nav-groups-tab" data-toggle="tab" href="#nav-groups" role="tab"
                           aria-controls="nav-groups" aria-selected="true">{{__('Groups')}}</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile"
                           role="tab"
                           aria-controls="nav-profile" aria-selected="false">{{__('Permissions')}}</a>
                        <a class="nav-item nav-link" id="nav-tokens-tab" data-toggle="tab" href="#nav-tokens" role="tab"
                           aria-controls="nav-tokens" aria-selected="false">{{__('API Tokens')}}</a>

                    </div>
                </nav>
                <div class="container mt-3">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                             aria-labelledby="nav-home-tab">
                            <div class="row">
                                <div class="col-8">
                                    <div class="card card-body">
                                        <h2>{{__('Name')}}</h2>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!! Form::label('firstname', __('First Name')) !!}
                                                {!! Form::text('firstname', null, ['id' => 'firstname','class'=>
                                                'form-control', 'v-model' => 'formData.firstname',
                                                'v-bind:class' => '{\'form-control\':true,
                                                \'is-invalid\':errors.firstname}']) !!}
                                                <div class="invalid-feedback" v-if="errors.firstname">
                                                    @{{errors.firstname[0]}}
                                                </div>
                                            </div>
                                            <div class="form-group col">
                                                {!! Form::label('lastname', __('Last Name'))!!}
                                                {!! Form::text('lastname', null, ['id' => 'lastname', 'rows' => 4,
                                                'class'=> 'form-control', 'v-model'
                                                => 'formData.lastname', 'v-bind:class' => '{\'form-control\':true,
                                                \'is-invalid\':errors.lastname}']) !!}
                                                <div class="invalid-feedback" v-if="errors.lastname">
                                                    @{{errors.lastname[0]}}
                                                </div>
                                            </div>
                                        </div>
                                        <h2 class="mt-2">{{__('Contact Information')}}</h2>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!! Form::label('email', __('Email')) !!}
                                                {!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=>
                                                'form-control', 'v-model'
                                                => 'formData.email', 'v-bind:class' => '{\'form-control\':true,
                                                \'is-invalid\':errors.email}']) !!}
                                                <div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}
                                                </div>
                                            </div>
                                            <div class="form-group col">
                                                {!! Form::label('phone', __('Phone')) !!}
                                                {!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control',
                                                'v-model' => 'formData.phone',
                                                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}'])
                                                !!}
                                                <div class="invalid-feedback" v-if="errors.phone">@{{errors.phone}}
                                                </div>
                                            </div>
                                        </div>
                                        <h2 class="mt-2">{{__('Address')}}</h2>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!! Form::label('address', __('Address')) !!}
                                                {!! Form::text('address', null, ['id' => 'address','class'=>
                                                'form-control', 'v-model' => 'formData.address',
                                                'v-bind:class' => '{\'form-control\':true,
                                                \'is-invalid\':errors.address}']) !!}
                                                <div class="invalid-feedback" v-if="errors.address">
                                                    @{{errors.address}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!! Form::label('city', __('City')) !!}
                                                {!! Form::text('city', null, ['id' => 'city', 'rows' => 4, 'class'=>
                                                'form-control', 'v-model'
                                                => 'formData.city', 'v-bind:class' => '{\'form-control\':true,
                                                \'is-invalid\':errors.city}']) !!}
                                                <div class="invalid-feedback" v-if="errors.city">@{{errors.city}}</div>
                                            </div>
                                            <div class="form-group col">
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
                                                <div class="invalid-feedback" v-if="errors.state">@{{errors.state}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!! Form::label('postal', __('Postal code')) !!}
                                                {!! Form::text('postal', null, ['id' => 'postal', 'rows' => 4, 'class'=>
                                                'form-control', 'v-model'
                                                => 'formData.postal', 'v-bind:class' => '{\'form-control\':true,
                                                \'is-invalid\':errors.postal}']) !!}
                                                <div class="invalid-feedback" v-if="errors.postal">@{{errors.postal}}
                                                </div>
                                            </div>
                                            <div class="form-group col">
                                                {!! Form::label('country', __('Country')) !!}
                                                <b-form-select v-model="formData.country" :options="countries" placeholder="Select" class="form-control">
                                                    <template slot="first">
                                                        <option :value="null" disabled>{{__('Select')}}</option>
                                                    </template>
                                                </b-form-select>
                                                <div class="invalid-feedback" v-if="errors.country">
                                                    @{{errors.country}}
                                                </div>
                                            </div>
                                        </div>
                                        <h2 class="mt-2">{{__('Localization')}}</h2>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!!Form::label('datetime_format', __('Date format'));!!}
                                                <b-form-select id="datetime_format" v-model="formData.datetime_format" class="form-control" :options="datetimeFormats">
                                                  <template slot="first">
                                                    <option :value="null" disabled>@{{appFormat}}</option>
                                                  </template>
                                                </b-form-select>
                                                <div class="invalid-feedback" v-if="errors.email">
                                                    @{{errors.datetime_format}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col">
                                                {!!Form::label('timezone', __('Time zone'));!!}
                                                <b-form-select id="timezone" v-model="formData.timezone" class="form-control" :options="timezones">
                                                  <template slot="first">
                                                    <option :value="null" disabled>@{{appTimezone}}</option>
                                                  </template>
                                                </b-form-select>
                                                <div class="invalid-feedback" v-if="errors.email">@{{errors.timezone}}
                                                </div>
                                            </div>

                                            <div class="form-group col">
                                                {!! Form::label('language', __('Language')) !!}
                                                <b-form-select v-model="formData.language" :options="langs" class="form-control"></b-form-select>
                                                <div class="invalid-feedback" v-if="errors.language">
                                                    @{{errors.language}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right mt-2">
                                            {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                                            {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'profileUpdate']) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="card card-body">
                                        <div align="center" data-toggle="modal" data-target="#updateAvatarModal">
                                            <avatar-image size="150" class-image="m-1"
                                                          :input-data="options"></avatar-image>
                                        </div>
                                        <div class="form-group">
                                            @include('shared.input',
                                                ['type' => 'text', 'name' => 'username', 'label' => __('Username')]
                                            )
                                        </div>

                                        <div class="form-group">
                                            {!!Form::label('status', __('Status')) !!}
                                            {!!Form::select('size', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'],
                                            'formData.status', ['class'=> 'form-control', 'v-model'=> 'formData.status',
                                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']);!!}
                                            <div class="invalid-feedback" v-if="errors.email">@{{errors.status}}</div>
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
                                                    {!! Form::password('password', ['id' => 'password', 'rows' => 4,
                                                    'class'=> 'form-control', 'v-model'
                                                    => 'formData.password', 'autocomplete' => 'new-password', '@input' =>
                                                    'props.updatePassword($event.target.value)',
                                                    'v-bind:class' => '{\'form-control\':true,
                                                    \'is-invalid\':errors.password}']) !!}
                                                </div>
                                            </vue-password>
                                        </div>
                                        <div class="form-group">
                                            {!! Form::label('confPassword', __('Confirm Password')) !!}
                                            {!! Form::password('confPassword', ['id' => 'confPassword', 'rows' => 4,
                                            'class'=> 'form-control', 'v-model'
                                            => 'formData.confPassword', 'autocomplete' => 'new-password', 'v-bind:class' =>
                                            '{\'form-control\':true, \'is-invalid\':errors.password}']) !!}
                                            <div class="invalid-feedback"
                                                 :style="{display: (errors.password) ? 'block' : 'none' }"
                                                 v-if="errors.password">@{{errors.password[0]}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="tab-pane fade show" id="nav-groups" role="tabpanel"
                             aria-labelledby="nav-groups-tab">
                            <div class="row">
                                <div class="col">
                                </div>
                                <div class="col-8" align="right">
                                    <button type="button" class="btn btn-action text-light" data-toggle="modal"
                                            data-target="#addUserToGroup" @click="loadGroups">
                                        <i class="fas fa-plus"></i>
                                        {{__('Add User To Group')}}
                                    </button>
                                </div>
                            </div>

                            <div id="groups-listing">
                                <groups-listing ref="groupsListing" filter="" :member_id="formData.id"/>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                            <div class="accordion card card-body" id="accordionPermissions">
                                <label>
                                    <input type="checkbox" v-model="formData.is_administrator"
                                           @input="adminHasChanged = true">
                                    {{__('Make this user a Super Admin')}}
                                </label>
                                <label class="mb-3">
                                    <input type="checkbox" v-model="selectAll" @click="select"
                                           :disabled="formData.is_administrator">
                                    {{__('Assign all permissions to this user')}}
                                </label>
                                @include('admin.shared.permissions')
                                <div class="text-right mt-2">
                                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose'])!!}
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'permissionUpdate'])!!}
                            </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-tokens" role="tabpanel" aria-labelledby="nav-tokens-tab">
                            <div v-if="!isCurrentUser">
                                {{__('Only the logged in user can create API tokens')}}
                            </div>
                            <div v-if="isCurrentUser">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>{{__('ID')}}</th>
                                        <th>{{__('Created At')}}</th>
                                        <th>{{__('Expires At')}}</th>
                                        <th>{{__('Delete')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="token in apiTokens">
                                        <td>@{{ token.id.substr(0,7) }}</td>
                                        <td>@{{ moment(token.created_at).format() }}</td>
                                        <td>@{{ moment(token.expires_at).format() }}</td>
                                        <td>
                                            <a style="cursor: pointer" @click='deleteToken(token.id)'>
                                                <i class="fas fa-trash-alt fa-lg" style="cursor: pointer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if='apiTokens.length == 0'>
                                        <td colspan="4">{{__('User has no tokens.')}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="form-group" v-if="newToken != null">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{__("Make sure you copy your access token now. You won't be able to see it again.")}}
                                    </div>
                                    <button @click="copyTextArea" class="btn btn-secondary">
                                        <i class="fas fa-paste"></i>
                                        {{__('Copy Token To Clipboard')}}
                                    </button>
                                    <textarea ref="text" style="height: 400px" class="form-control">@{{ newToken.accessToken }}</textarea>
                                </div>
                                <hr class="mt-0">
                                <button class="btn btn-secondary float-right" @click="generateToken">
                                    {{__('Generate New Token')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" role="dialog" id="addUserToGroup">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Add User To Group')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="onCloseAddUserToGroup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('groups', __('Groups'))!!}
                            <multiselect v-model="selectedGroup"
                                         placeholder="{{__('Select group or type here to search groups')}}"
                                         :options="groups"
                                         :multiple="true"
                                         track-by="name"
                                         :custom-label="customLabel"
                                         :show-labels="false"
                                         :searchable="true"
                                         :internal-search="false"
                                         @search-change="loadGroups"
                                         label="name">

                                <template slot="tag" slot-scope="props">
                                    <span class="multiselect__tag  d-flex align-items-center"
                                          style="width:max-content;">
                                        <span class="option__desc mr-1">
                                            <span class="option__title">@{{ props.option.name }}</span>
                                        </span>
                                        <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)"
                                           class="multiselect__tag-icon"></i>
                                    </span>
                                </template>

                                <template slot="option" slot-scope="props">
                                    <div class="option__desc d-flex align-items-center">
                                        <span class="option__title mr-1">@{{ props.option.name }}</span>
                                    </div>
                                </template>
                            </multiselect>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"
                                @click="onCloseAddUserToGroup">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="saveUserToGroup">
                            {{__('Save')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="modal" tabindex="-1" role="dialog" id="updateAvatarModal" ref="updateAvatarModal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Upload Avatar')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div>
                        <div v-if="!image" class="no-avatar"
                             align="center">{{__('Click the browse button below to get started')}}</div>
                        <div align="center">
                            <button @click="browse" class="btn btn-secondary mt-5 mb-2"><i class="fas fa-upload"></i>
                                {{__('Browse')}}
                            </button>
                        </div>
                        <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie"
                                     :viewport="{ width: 380, height: 380, type: 'circle' }"
                                     :boundary="{ width: 400, height: 400 }" :enable-orientation="false"
                                     :enable-resize="false">
                        </vue-croppie>
                    </div>
                    <input type="file" class="custom-file-input" ref="customFile" @change="onFileChange">
                </div>

                <div class="modal-footer">
                    <button @click="hideModal" class="btn btn-outline-secondary">
                        {{__('Cancel')}}
                    </button>

                    <button @click="saveAndEmit" class="btn btn-secondary">
                        {{__('Save')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/users/edit.js')}}"></script>

    <script>
      new Vue({
        el: '#updateAvatarModal',
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

    <script>
      var formVueInstance = new Vue({
        el: '#editUser',
        data() {
          return {
            formData: @json($user),
            langs: @json($availableLangs),
            timezones: @json($timezones),
            appTimezone: @json($appTimezone),
            datetimeFormats: @json($datetimeFormats),
            appFormat: @json($appFormat),
            countries: @json($countries),
            image: '',
            errors: {
              username: null,
              firstname: null,
              lastname: null,
              email: null,
              password: null,
              status: null,
              is_administrator: null,
            },
            permissions: @json($all_permissions),
            userPermissionNames: @json($permissionNames),
            selectedPermissions: [],
            selectAll: false,
            newToken: null,
            adminHasChanged: false,
            apiTokens: [],
            currentUserId: {{ Auth::user()->id }},
            options: [{
              src: @json($user['avatar']),
              title: @json($user['fullname']),
              initials: @json($user['firstname'][0]) + @json($user['lastname'][0])
            }],
            selectedGroup: [],
            groups: [],
          }
        },
        created() {
          this.hasPermission()
        },
        computed: {
          isCurrentUser() {
            return this.currentUserId == this.formData.id
          },
        },
        mounted() {
          let created = (new URLSearchParams(window.location.search)).get('created');
          if (created) {
            ProcessMaker.alert('{{__('The user was successfully created')}}', 'success');
          }
          this.loadTokens();
        },
        watch: {
          selectedPermissions: function () {
            if (this.selectedPermissions.length !== this.permissions.length) {
              this.selectAll = false;
            }
          }
        },
        methods: {
          checkCreate(sibling, $event) {
            let self = $event.target.value;
            if (this.selectedPermissions.includes(self)) {
              this.selectedPermissions.push(sibling);
            }
          },
          checkEdit(sibling, $event) {
            let self = $event.target.value;
            if (!this.selectedPermissions.includes(self)) {
              this.selectedPermissions = this.selectedPermissions.filter(function (el) {
                return el !== sibling;
              });
            }
          },
          copyTextArea() {
            this.$refs.text.select();
            document.execCommand('copy');
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
          onClose() {
            window.location.href = '/admin/users';
          },
          validatePassword() {
            if (!this.formData.password && !this.formData.confpassword) {
              delete this.formData.password;
              return true;
            }
            if (this.formData.password.trim() === '' && this.formData.confpassword.trim() === '') {
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
          profileUpdate($event) {
            this.resetErrors();
            if (!this.validatePassword()) return false;
            ProcessMaker.apiClient.put('users/' + this.formData.id, this.formData, {context: this})
              .then(response => {
                ProcessMaker.alert('{{__('User Updated Successfully ')}}', 'success');
                if ($event !== false) {
                  this.onClose();
                }
              })
              .catch(error => {
                console.log(error);
                this.errors = error.response.data.errors;
              });
          },
          permissionUpdate() {
            if (this.adminHasChanged) {
              this.profileUpdate(false)
            }
            ProcessMaker.apiClient.put("/permissions", {
              permission_names: this.selectedPermissions,
              user_id: this.formData.id
            })
              .then(response => {
                ProcessMaker.alert('{{__('User Permissions Updated Successfully ')}}', 'success');
                this.onClose();
              })
          },
          hasPermission() {
            if (this.userPermissionNames) {
              this.selectedPermissions = this.userPermissionNames;
            }
          },
          select() {
            this.selectedPermissions = [];
            if (!this.selectAll) {
              for (let permission in this.permissions) {
                this.selectedPermissions.push(this.permissions[permission].name);
              }
            }
          },
          loadTokens() {
            ProcessMaker.apiClient({
              method: 'GET',
              url: '/oauth/personal-access-tokens',
              baseURL: '/'
            })
              .then((result) => {
                this.apiTokens = result.data
              })
          },
          generateToken() {
            ProcessMaker.apiClient({
              method: 'POST',
              url: '/oauth/personal-access-tokens',
              baseURL: '/',
              data: {
                name: 'API Token',
                scopes: []
              }
            })
              .then((result) => {
                this.newToken = result.data;
                this.loadTokens();
                ProcessMaker.alert("{{__('Access token generated successfully')}}", "success");
              })
          },
          deleteToken(tokenId) {
            ProcessMaker.confirmModal(
              __("Caution!"),
              __("Are you sure to delete the token ") + tokenId.substr(0, 7) +
              __("? Any services using it will no longer have access."),
              "",
              () => {
                ProcessMaker.apiClient({
                  method: 'DELETE',
                  url: '/oauth/personal-access-tokens/' + tokenId,
                  baseURL: '/',
                })
                  .then((result) => {
                    this.loadTokens();
                    this.newToken = null;
                  })
              }
            );
          },
          customLabel(options) {
            return `${options.name}`
          },
          loadGroups(filter) {
            filter = typeof filter === 'string' ? '?filter=' + filter + '&' : '?';
            ProcessMaker.apiClient
              .get(
                "group_members_available" + filter +
                "member_id=" + this.formData.id +
                "&member_type=ProcessMaker\\Models\\User"
              )
              .then(response => {
                this.groups = response.data.data
              });
          },
          onCloseAddUserToGroup() {
            this.selectedGroup = [];
          },
          saveUserToGroup() {
            let that = this;
            that.selectedGroup.forEach(function (group) {
              ProcessMaker.apiClient
                .post('group_members', {
                  'group_id': group.id,
                  'member_type': 'ProcessMaker\\Models\\User',
                  'member_id': that.formData.id
                })
                .then(response => {
                  that.$refs['groupsListing'].fetch();
                  $('#addUserToGroup').modal('hide');
                  that.selectedGroup = [];
                });
            });
          }
        }
      });
    </script>
@endsection


@section('css')
    <style>
        .inline-input {
            margin-right: 6px;
        }

        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }

        .input-and-select {
            width: 212px;
        }

        .multiselect__element span img {
            border-radius: 50%;
            height: 20px;
        }

        .multiselect__tags-wrap {
            display: flex !important;
        }

        .multiselect__tags-wrap img {
            height: 15px;
            border-radius: 50%;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }

        .multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }

        .multiselect__tag {
            background: #788793 !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection
