@if($section==="template")
<b-modal v-model="accessTokenPopup.show" size="lg" centered title="{{__('Request token')}}" v-cloak>
  <div class="editor-container">
    <div class="form-group">
      {!! Form::label('url', __('Url Token')) !!}
      {!! Form::text('url', null, ['class'=> 'form-control', 'v-model'=> 'credentials.url',
      'v-bind:class'
      => '{\'form-control\':true, \'is-invalid\':errors.url}']) !!}
      <div class="invalid-feedback" v-if="errors.url">@{{errors.url[0]}}
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('clientId', __('Client ID')) !!}
      {!! Form::text('clientId', null, ['class'=> 'form-control', 'v-model'=>
      'credentials.client_id',
      'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.clientId}']) !!}
      <div class="invalid-feedback" v-if="errors.clientId">@{{errors.clientId[0]}}
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('clientSecret', __('Client Secret')) !!}
      {!! Form::text('clientSecret', null, ['class'=> 'form-control', 'v-model'=>
      'credentials.client_secret', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.clientSecret}']) !!}
      <div class="invalid-feedback" v-if="errors.clientSecret">@{{errors.clientSecret[0]}}
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('user', __('User')) !!}
      {!! Form::text('user', null, ['v-model'=> 'credentials.username', 'v-bind:class' =>
      '{\'form-control\':true, \'is-invalid\':errors.user}']) !!}
      <div class="invalid-feedback" v-if="errors.user">@{{errors.user[0]}}
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('password', __('Password')) !!}
      {{ Form::password('password', ['class'=> 'form-control', 'v-model'=> 'credentials.password', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.password}']) }}
      <div class="invalid-feedback" v-if="errors.password">@{{errors.password[0]}}
      </div>
    </div>

    <div v-show="accessTokenPopup.progress < 100" class="progress">
      <div class="progress-bar progress-bar-animated progress-bar-striped progress-bar-animated" role="progressbar">
      </div>
    </div>
  </div>
  <div slot="modal-footer">
    <b-button @click="getAccessToken" class="btn btn-secondary">
      <i class="fas fa-cog"></i> {{ __('Get Access Token') }}
    </b-button>
    <b-button @click="closeAccessTokenPopup" class="btn btn-secondary">
      {{ __('CLOSE') }}
    </b-button>
  </div>
</b-modal>
@else
<script>
  mixins.push({
    data() {
      return {
        accessTokenPopup: {
          show: false,
          progress: 100,
        },
      };
    },
    methods: {
      openGetAccessToken() {
        this.accessTokenPopup.show = true;
      },
      closeAccessTokenPopup() {
        this.accessTokenPopup.show = false;
      },
      getAccessToken() {
        this.accessTokenPopup.show = true;
        this.accessTokenPopup.progress = 0;
        this.credentials.grant_type = "password";
        window.ProcessMaker.apiClient.post(`datasources/${this.formData.id}/test`, {
          immediate: true,
          data: {
            purpose: "get_access_token",
            url: this.credentials.url,
            method: this.credentials.method,
            body_type: "form-data",
            body: JSON.stringify({
              "username": this.credentials.username,
              "password": this.credentials.password,
              "grant_type": this.credentials.grant_type,
              "client_id": this.credentials.client_id,
              "client_secret": this.credentials.client_secret,
            }),
          }
        }).then((response) => {
          this.accessTokenPopup.progress = 100;
          this.credentials.token = response.data.access_token;
          this.accessTokenPopup.show = false;
        });
      },
    },
  });
</script>
@endif
