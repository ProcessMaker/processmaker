@extends('layouts.layout')

@section('title')
  {{__('Connected Accounts')}}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
    __('Admin') => route('admin.index'),
    __('Users') => route('users.index'),
    __('Edit') . " " . $user->fullname => null,
    __('Connected Accounts') => null,
  ]])
@endsection
@section('content')
  <div class="container" id="connectedAccounts">
    <div class="card card-body">
      <h4 class="mt-2 pb-3 page-title">{{__('Connected Accounts')}}</h4>
      <ul class="accounts-list w-100" id="color-list">
        <li class="accounts-list-item" v-for="account in accounts">
          <img :src="account.icon" :alt="account.name + 'icon'" width="45px"/>
          <h5>@{{account.name}}</h5>
          <p>@{{account.description}}</p>
        </li>
      </ul>
    </div>
  </div>


  <pm-modal
    ref="editConnectionModal"
    id="editConnectionModal"
    title="{{__('Edit Connection')}}"
    style="display: none;"
  >
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
        el: '#editConnectionModal',
        data() {
          };
        },
        methods: {
          hideModal() {
            this.$refs.editConnectionModal.hide();
          },
        }
      });
    </script>

    <script>
      var formVueInstance = new Vue({
        el: '#connectedAccounts',
        data() {
          return {
            accounts:[
            {
              id: 1,
              name: 'Gmail',
              description: 'Provide automated responses, generate drafts, and assist with email composition.',
              icon: 'https://img.icons8.com/color/48/gmail--v1.png',
            },
            {
              id: 2,
              name: 'Slack',
              description: 'Assist with task management, scheduling, and provide intelligent message responses.',
              icon: 'https://img.icons8.com/color/48/slack-new.png',
            },
          ],
            meta: @json(config('users.properties')),
            formData: @json($user),
            userId: @json($user->id),
            errors: {
              username: null,
              firstname: null,
              lastname: null,
              email: null,
              password: null,
              status: null,
              is_administrator: null,
            },
          }
        },
        computed: {
        },
        mounted() {
        },
        watch: {
        },
        methods: {
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
      },
      });
    </script>
@endsection


@section('css')
    <style>

    .page-title {
      color: #556271;
      font-size: 21px;
    }
    </style>
@endsection
