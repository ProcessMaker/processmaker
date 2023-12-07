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
  <div class="container h-100" id="connectedAccounts">
    <div class="card card-body h-100">
      <h4 class="mt-2 pb-3 page-title">{{__('Connected Accounts')}}</h4>
      <ul class="accounts-list w-100 pl-0">
        <li class="accounts-list-item d-flex align-items-start py-3 mt-3" v-for="account in accounts">
          <div class="d-flex align-items-start mr-3">
            <img :src="account.icon" :alt="account.name + 'icon'" width="45px"/>
          </div>
          <div class="d-flex flex-column flex-grow-1">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <h5 class="account-name mb-0">@{{account.name}}</h5>
                <p class="account-description mb-0">@{{account.description}}</p>
              </div>
                <div class="d-flex align-items-center">
                <button class="edit-btn" @click="showModal()">Edit</button>
                <b-badge pill variant="success" class="ml-3 connection-status">
                    <i class="fa fa-check"></i>
                    Connected
                </b-badge>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <pm-modal
    ref="editConnectionModal"
    id="editConnectionModal"
    title="{{__('Edit Connection')}}"
    style="display: none;"
    :ok-disabled="disabled"
    :set-custom-buttons="true"
    :custom-buttons="customModalButtons"
    @hidden="onClose"
    @close="onClose"
    @onSubmit="onSubmit"
  >
    <div class="form-group">
      {!! Form::label('url', __('URL')) !!}
      {!! Form::text('url', null, ['id' => 'url','class'=> 'form-control', 'v-model' =>
      'formData.url', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.url}',
      'v-bind:placeholder' => '$t("Placeholder")',
      'required', 'aria-required' => 'true']) !!}
      <div class="invalid-feedback" role="alert" v-for="url in errors.url">@{{url}}</div>
    </div>
    <div class="form-group">
      {!! Form::label('user', __('User')) !!}
      {!! Form::text('user', null, ['id' => 'user', 'rows' => 4, 'class'=>
      'form-control', 'v-model' => 'formData.user',
      'v-bind:placeholder' => '$t("Placeholder")',
      'v-bind:class' => '{\'form-control\':true,\'is-invalid\':errors.user}']) !!}
      <div class="invalid-feedback" role="alert" v-for="user in errors.user">@{{user}}
      </div>
    </div>
    <div class="form-group">
      {!! Form::label('accessKey', __('Access Key')) !!}
      {!! Form::text('accessKey', null, ['id' => 'accessKey', 'rows' => 4, 'class'=>
      'form-control', 'v-model' => 'formData.accessKey',
      'v-bind:placeholder' => '$t("Placeholder")',
      'v-bind:class' => '{\'form-control\':true,\'is-invalid\':errors.accessKey}']) !!}
      <div class="invalid-feedback" role="alert" v-for="accessKey in errors.accessKey">@{{accessKey}}
      </div>
    </div>
  </pm-modal>
@endsection

@section('js')
  <script>
    var modalVueInstance = new Vue({
      el: '#editConnectionModal',
      data() {
        return {
          customModalButtons: [
            {"content": "Cancel", "action": "close", "variant": "secondary", "size": "md"},
            {"content": "OK", "action": "onSubmit", "variant": "primary", "size": "md"},
          ],
          formData: {},
          errors: {
            'url': null,
            'user': null,
            'accessKey': null
          },
          disabled: false
        }
      },
      methods: {
        hideModal() {
          modalVueInstance.$refs.editConnectionModal.hide();
        },
        onClose() {
          this.hideModal();
          this.resetFormData();
          this.resetErrors();
        },
        resetFormData() {
          this.formData = Object.assign({}, {
            url: null,
            user: null,
            accessKey: null
          });
        },
        resetErrors() {
          this.errors = Object.assign({}, {
            url: null,
            user: null,
            accessKey: null
          });
        },
        onSubmit() {
          this.resetErrors();
          //single click
          if (this.disabled) {
            return
          }
          this.disabled = true;

          //TODO: HANDLE CONNECTION UPDATE

        }
      }
    });
  </script>

  <script>
    var formVueInstance = new Vue({
      el: '#connectedAccounts',
      data() {
        return {
          //TODO: REMOVE THIS DUMMY ACCOUNT DATA
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
        }
      },
      methods: {
        showModal() {
          modalVueInstance.$refs.editConnectionModal.show();
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

  .accounts-list-item {
    border-bottom: 1px solid #C4C8CC;
  }

  .account-name {
    font-size: 18px;
    font-weight: 600;
  }

  .account-description {
    font-size: 14px;
    font-weight: 400;
    color: #6C757D;
  }

  .edit-btn {
    border: none;
    background-color: #FFFFFF;
    color: #6C757D;
    font-size: 14px;
  }

  .connection-status {
    border-radius: 8px;
    font-size: 16px;
    font-weight: 400;
    padding: 0.75rem;
  }
  </style>
@endsection
