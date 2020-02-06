@extends('layouts.layout')

@section('title')
    {{__('Edit Users')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Users') => route('users.index'),
        __('Edit') . " " . $user->fullname => null,
    ]])
@endsection
@section('content')
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
                <div class="container mt-0 border-top-0 p-3 card card-body">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                             aria-labelledby="nav-home-tab">
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
                        <div class="tab-pane fade show" id="nav-groups" role="tabpanel"
                             aria-labelledby="nav-groups-tab">
                             <div class="input-group w-100 mb-3">
                                 <input v-model="userGroupsFilter" class="form-control" placeholder="{{__('Search')}}">
                                 <div class="input-group-append">
                                     <button type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                                 </div>
                             </div>
                            <div id="user-groups-listing">
                                <user-groups-listing
                                    :filter="userGroupsFilter"
                                    ref="userGroupsListing"
                                    :user-id='@json($user->id)'
                                    :current-groups='@json($user->groups)'
                                />
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'onSaveGroups']) !!}
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                            <div class="accordion" id="accordionPermissions">
                                <div class="mb-2 custom-control custom-switch">
                                    <input v-model="formData.is_administrator" type="checkbox" class="custom-control-input" id="is_administrator" @input="adminHasChanged = true">
                                    <label class="custom-control-label" for="is_administrator">{{ __('Make this user a Super Admin') }}</label>
                                </div>
                                <div class="mb-3 custom-control custom-switch">
                                    <input v-model="selectAll" type="checkbox" class="custom-control-input" id="selectAll" @click="select" :disabled="formData.is_administrator">
                                    <label class="custom-control-label" for="selectAll">{{ __('Assign all permissions to this user') }}</label>
                                </div>
                                @include('admin.shared.permissions')
                                <div class="d-flex justify-content-end mt-3">
                                    {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose'])!!}
                                    {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'permissionUpdate'])!!}
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-tokens" role="tabpanel" aria-labelledby="nav-tokens-tab">
                            <div>
                                <div class="d-flex justify-content-end mb-3">
                                    <button type="button" class="btn btn-secondary" @click="generateToken">
                                        <i class="fas fa-plus"></i> {{__('Token')}}
                                    </button>
                                </div>

                                <user-tokens-listing :user_id="formData.id" ref="tokenList"></user-tokens-listing>

                                <div class="modal" tabindex="-1" role="dialog" id="newTokenModal" ref="newTokenModal">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content p-3">
                                            <div class="modal-header p-0 mb-3">
                                                <h5 class="modal-title m-0">{{__('New Token')}}</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body p-0 mb-3" v-if="newToken != null">

                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{__("Make sure you copy your access token now. You won't be able to see it again.")}}
                                                </div>

                                                <div>
                                                    <textarea ref="text" style="height: 400px" class="form-control">@{{ newToken.accessToken }}</textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer p-0">
                                                <div class="d-flex w-100">
                                                    <button type="button" @click="copyTextArea" class="btn btn-secondary">
                                                        <i class="fas fa-paste"></i>
                                                        {{__('Copy Token To Clipboard')}}
                                                    </button>
                                                    <button type="button" @click="hideNewTokenModal" class="ml-auto btn btn-outline-secondary">
                                                        {{__('Close')}}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                            <button type="button" @click="browse" class="btn btn-secondary mt-5 mb-2"><i class="fas fa-upload"></i>
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
                    <button type="button" @click="hideModal" class="btn btn-outline-secondary">
                        {{__('Cancel')}}
                    </button>

                    <button type="button" @click="saveAndEmit" class="btn btn-secondary">
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
      var modalVueInstance = new Vue({
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
          deleteAvatar() {
              let optionValues = formVueInstance.$data.options[0];
              optionValues.src = null;
              formVueInstance.$data.options.splice(0, 1, optionValues)
              formVueInstance.$data.image = false;
              formVueInstance.$data.formData.avatar = false;
              window.ProcessMaker.events.$emit('update-profile-avatar');
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
            datetimeFormats: @json($datetimeFormats),
            countries: @json($countries),
            states: @json($states),
            userId: @json($user->id),
            image: '',
            status: @json($status),
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
              initials: @json(mb_substr($user['firstname'], 0, 1)) + @json(mb_substr($user['lastname'], 0, 1))
            }],
            selectedGroup: [],
            groups: [],
            userGroupsFilter: '',
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
        },
        watch: {
          selectedPermissions: function () {
            this.selectAll = this.areAllPermissionsSelected();
          }
        },
        methods: {
          areAllPermissionsSelected() {
            return this.selectedPermissions.length === this.permissions.length;
          },
          checkCreate(sibling, $event) {
            let self = $event.target.value;
            if (this.selectedPermissions.includes(self)) {
              this.selectedPermissions.push(sibling);
            }
            Vue.set(this, 'selectedPermissions', this.selectedPermissions.filter((v, i, arr) => arr.indexOf(v) === i));
          },
          checkEdit(sibling, $event) {
            let self = $event.target.value;
            if (!this.selectedPermissions.includes(self)) {
              this.selectedPermissions = this.selectedPermissions.filter(function (el) {
                return el !== sibling;
              });
            }
            Vue.set(this, 'selectedPermissions', this.selectedPermissions.filter((v, i, arr) => arr.indexOf(v) === i));
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
          deleteAvatar() {
              modalVueInstance.deleteAvatar();
          },
          openNewTokenModal() {
            $('#newTokenModal').modal("show");
          },
          hideNewTokenModal() {
            $('#newTokenModal').modal("hide");
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
                window.ProcessMaker.events.$emit('update-profile-avatar');
              })
              .catch(error => {
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

          },
          generateToken() {
            ProcessMaker.apiClient({
              method: 'POST',
              url: '/users/' + {{ $user->id }} + '/tokens',
              data: {
                name: 'API Token',
                scopes: []
              }
            })
              .then((result) => {
                this.newToken = result.data.token;
                this.newToken.accessToken = result.data.accessToken;
                this.loadTokens();
                this.$refs.tokenList.fetch();
                this.openNewTokenModal();
              })
          },
          customLabel(options) {
            return `${options.name}`
          },
          onSaveGroups() {
            let groups = this.$refs.userGroupsListing.userGroups.join(',');

            ProcessMaker.apiClient.put(`users/${this.formData.id}/groups`, {
                groups: groups
            })
            .then(response => {
              ProcessMaker.alert('{{__('Groups Updated Successfully ')}}', 'success');
            })
            .catch(error => {
              this.errors = error.response.data.errors;
            });
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
