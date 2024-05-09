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
                        @can('view-security-logs')
                            <a class="nav-item nav-link" id="nav-logs-tab" data-toggle="tab" href="#nav-logs" role="tab"
                               aria-controls="nav-logs" aria-selected="false">{{__('Security Logs')}}</a>
                        @endcan
                    </div>
                </nav>
                <div class="container mt-0 border-top-0 p-3 card card-body">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane show active" id="nav-home" role="tabpanel"
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
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'profileUpdate','id'=>'saveUser']) !!}
                            </div>
                        </div>
                        <div class="tab-pane show" id="nav-groups" role="tabpanel"
                             aria-labelledby="nav-groups-tab">
                             <div class="input-group w-100 mb-3">
                                 <input id="search-groups" v-model="userGroupsFilter" class="form-control" placeholder="{{__('Search')}}" aria-label="{{__('Search')}}">
                                 <div class="input-group-append">
                                     <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
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
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'onSaveGroups','id'=>'saveGroups']) !!}
                            </div>
                        </div>
                        <div class="tab-pane" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                            <div class="accordion" id="accordionPermissions">
                                <div class="mb-2 custom-control custom-switch">
                                    <input id="is_administrator" type="checkbox" v-model="formData.is_administrator"  class="custom-control-input">
                                    <label for="is_administrator" class="custom-control-label">{{ __('Make this user a Super Admin') }}</label>
                                </div>
                                <div class="mb-3 custom-control custom-switch">
                                    <input id="selectAll" type="checkbox" v-model="selectAll" class="custom-control-input" @click="select" :disabled="formData.is_administrator">
                                    <label for="selectAll" class="custom-control-label">{{ __('Assign all permissions to this user') }}</label>
                                </div>
                                @include('admin.shared.permissions')
                                <div class="d-flex justify-content-end mt-3">
                                    {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose'])!!}
                                    {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'permissionUpdate','id'=>'savePermissions'])!!}
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="nav-tokens" role="tabpanel" aria-labelledby="nav-tokens-tab">
                            <div>
                                <div class="d-flex justify-content-end mb-3">
                                    <button type="button" aria-label="{{__('New Token')}}" class="btn btn-secondary" @click="generateToken">
                                        <i class="fas fa-plus"></i> {{__('Token')}}
                                    </button>
                                </div>

                                <user-tokens-listing :user_id="formData.id" ref="tokenList"></user-tokens-listing>

                                <b-modal
                                    id="newTokenModal"
                                    ref="newTokenModal"
                                    title="{{__('New Token')}}"
                                    footer-class="pm-modal-footer"
                                    no-close-on-backdrop
                                    centered
                                >
                                    <template v-if="newToken != null">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            {{__("Make sure you copy your access token now. You won't be able to see it again.")}}
                                        </div>

                                        <div>
                                            <textarea id="generated-token" ref="text" style="height: 400px" class="form-control" aria-label="Generated Token">@{{ newToken.accessToken }}</textarea>
                                        </div>
                                    </template>
                                    <template #modal-footer>
                                        <button type="button" @click="copyTextArea" class="btn btn-secondary">
                                            <i class="fas fa-paste"></i>
                                            {{__('Copy Token To Clipboard')}}
                                        </button>
                                        <button type="button" @click="hideNewTokenModal" class="ml-auto btn btn-outline-secondary">
                                            {{__('Close')}}
                                        </button>
                                    </template>
                                </b-modal>
                            </div>
                        </div>
                        @can('view-security-logs')
                          <div class="tab-pane" id="nav-logs" role="tabpanel" aria-labelledby="nav-logs-tab">
                              <div>
                                  <security-logs-listing :user-id="@json($user->id)"></security-logs-listing>
                              </div>
                          </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>


    <pm-modal ref="updateAvatarModal" id="updateAvatarModal" title="{{__('Upload Avatar')}}" @hidden="hiddenModal" @ok.prevent="saveAndEmit" style="display: none;">
        <div>
            <div v-if="!image" class="no-avatar"
                 align="center">{{__('Click the browse button below to get started')}}</div>
            <div align="center">
                <button type="button" @click="browse" class="btn btn-secondary mt-5 mb-2"><i class="fas fa-upload"></i>
                    {{__('Browse')}}
                </button>
            </div>
            <div align="center">
                {{__('Image types accepted: .gif, .jpg, .jpeg, .png')}}
            </div>
            <vue-croppie :style="{display: (image) ? 'block' : 'none' }" ref="croppie"
                         :viewport="{ width: 380, height: 380, type: 'circle' }"
                         :boundary="{ width: 400, height: 400 }" :enable-orientation="false"
                         :enable-resize="false">
            </vue-croppie>
        </div>
        <input id="upload-image" type="file" class="custom-file-input" accept=".gif,.jpg,.jpeg,.png,image/jpeg,image/gif,image/png" ref="customFile" @change="onFileChange" aria-label="{{__('Select a file')}}">
    </pm-modal>
@endsection

@section('js')
    <script src="{{mix('js/admin/users/edit.js')}}"></script>

    <script>
      var modalVueInstance = new Vue({
        el: '#updateAvatarModal',
        mixins:addons,
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
          hideModal() {
            this.$refs.updateAvatarModal.hide();
          },
          hiddenModal() {
            this.image = '';
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
        mixins:addons,
        el: '#editUser',
        data() {
          return {
            meta: @json(config('users.properties')),
            formData: @json($user),
            langsValues: @json($availableLangs, true),
            timezonesValues: @json($timezones, true),
            datetimeFormatsValues: @json($datetimeFormats, true),
            countriesValues: @json($countries, true),
            statesValues: @json($states, true),
            userId: @json($user->id),
            image: '',
            status: @json($status),
            global2FAEnabled: @json($global2FAEnabled),
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
            focusErrors: 'errors',
          }
        },
        created() {
          this.hasPermission()
          if (this.meta) {
            let keys = Object.keys(this.meta);
            if (!this.formData.meta) {
                this.formData.meta = {};
            }
            keys.forEach(key => {
               if (!this.formData.meta[key]) {
                   this.formData.meta[key] = null;
               }
            });
          }
        },
        computed: {
          isCurrentUser() {
            return this.currentUserId == this.formData.id
          },
          langs() {
            return this.formatDataSelect(this.langsValues);
          },
          timezones() {
            return this.formatDataSelect(this.timezonesValues);
          },
          datetimeFormats() {
            return this.formatDataSelect(this.datetimeFormatsValues);
          },
          countries() {
            return this.formatDataSelect(this.countriesValues);
          },
          states() {
            return this.formatDataSelect(this.statesValues);
          },
          state2FA() {
            return typeof this.formData.preferences_2fa != "undefined" && this.formData.preferences_2fa != null
                && this.formData.preferences_2fa.length > 0;
          }
        },
        mounted() {
          let created = (new URLSearchParams(window.location.search)).get('created');
          if (created) {
            ProcessMaker.alert(this.$t('The user was successfully created'), 'success');
          }
        },
        watch: {
          selectedPermissions: function () {
            this.selectAll = this.areAllPermissionsSelected();
          },
        },
        methods: {
          openAvatarModal() {
            modalVueInstance.$refs.updateAvatarModal.show();
          },
          formatDataSelect (objectData) {
            let data = [];
            for (const property in objectData) {
              data.push({
                value: property,
                text: objectData[property]
              })
            }
            return data;
          },
          areAllPermissionsSelected() {
            return this.selectedPermissions.length === this.permissions.length;
          },
          checkCreate(sibling, $event) {
            let self = $event.target.value;
            if (this.selectedPermissions.includes(self)) {
              this.selectedPermissions.push(sibling);
            }
            if (sibling.includes('processes') || self.includes('processes')) {
              this.checkProcessCategoryView(sibling, self);
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
            if (sibling.includes('processes') || self.includes('processes')) {
              this.checkProcessCategoryView(sibling, self);
            }
            Vue.set(this, 'selectedPermissions', this.selectedPermissions.filter((v, i, arr) => arr.indexOf(v) === i));
          },
          checkProcessCategoryView(sibling, self) {
            const viewProcessCategoriesPermission = 'view-process-categories';
            if (this.selectedPermissions.includes(self)) {
              this.selectedPermissions.push(viewProcessCategoriesPermission);
            }

            if (!this.selectedPermissions.includes(self) && !this.selectedPermissions.includes(sibling)) {
              this.selectedPermissions = this.selectedPermissions.filter(function (el) {
                return el !== viewProcessCategoriesPermission;
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
            this.$refs.newTokenModal.show();
          },
          hideNewTokenModal() {
            this.$refs.newTokenModal.hide();
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
            if (@json($enabled2FA) &&  this.global2FAEnabled.length === 0) {
              // User has not enabled two-factor authentication correctly
              ProcessMaker.alert(
                this.$t('The Two Step Authentication Method has not been set. Please contact your administrator.'),
                'warning'
              );
              return false;
            }
            console.log('saving....');
            if (!this.validatePassword()) return false;
            if (@json($enabled2FA) && typeof this.formData.preferences_2fa != "undefined" &&
              this.formData.preferences_2fa != null && this.formData.preferences_2fa.length < 1) return false;
            ProcessMaker.apiClient.put('users/' + this.formData.id, this.formData)
              .then(response => {
                ProcessMaker.alert(this.$t('User Updated Successfully '), 'success');
                if (this.formData.id == window.ProcessMaker.user.id) {
                  window.ProcessMaker.events.$emit('update-profile-avatar');
                }
              })
              .catch(error => {
                this.errors = error.response.data.errors;
              });
          },
          permissionUpdate() {
            ProcessMaker.apiClient.put("/permissions", {
              is_administrator: this.formData.is_administrator,
              permission_names: this.selectedPermissions,
              user_id: this.formData.id
            })
              .then(response => {
                ProcessMaker.alert(this.$t('User Permissions Updated Successfully'), 'success');
                if (this.userId === this.currentUserId) {
                  ProcessMaker.alert(this.$t('Please logout and login again to reflect permission changes'), 'warning');
                }
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
              ProcessMaker.alert(this.$t('Groups Updated Successfully '), 'success');
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
