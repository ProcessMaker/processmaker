<div class="card card-body">
    <required></required>
    <h5 class="mb-3 font-weight-bold">{{__('Profile')}}</h5>
    <h5 class="mb-3">{{__('General Information')}}</h5>
        <div class="row">
            <div class="form-group col">
                {{ html()->label(__('First Name') . '<small class="ml-1">*</small>', 'firstname') }}
                {{ html()->text('firstname')->id('firstname')->class('form-control')->attribute('v-model', 'formData.firstname')->attribute('v-bind:class', '{\'form-control\':true,
                \'is-invalid\':errors.firstname}')->required()->attribute('aria-required', 'true') }}
                <div class="invalid-feedback" role="alert" v-if="errors.firstname">
                    @{{errors.firstname[0]}}
                </div>
            </div>
            <div class="form-group col">
                {{ html()->label(__('Last Name') . '<small class="ml-1">*</small>', 'lastname') }}
                {{ html()->text('lastname')->id('lastname')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.lastname')->attribute('v-bind:class', '{\'form-control\':true,
                \'is-invalid\':errors.lastname}')->required()->attribute('aria-required', 'true') }}
                <div class="invalid-feedback" role="alert" v-if="errors.lastname">
                    @{{errors.lastname[0]}}
                </div>
            </div>
        </div>

        <div class="form-group">
            {{ html()->label(__('Job Title'), 'title') }}
            <b-form-input
              id="title"
              class="mb-2"
              v-model="formData.title"
              type="text"
              required
              placeholder="Job Title"
            ></b-form-input>
        </div>
<hr>
    <h5 class="mt-1 mb-3">{{__('Contact Information')}}</h5>

        <div class="form-group">
            {{ html()->label(__('Email') . '<small class="ml-1">*</small>', 'email') }}
            {{ html()->email('email')->id('email')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.email')->attribute('v-bind:class', '{\'form-control\':true,
            \'is-invalid\':errors.email}')->attribute('required', )->attribute('aria-required', 'true')->attribute('@input', 'checkEmailChange') }}
            <div class="invalid-feedback" role="alert" v-if="errors.email">@{{errors.email[0]}}
            </div>
        </div>
        <div class="form-group">
            {{ html()->label(__('Phone'), 'phone') }}
            {{ html()->text('phone')->id('phone')->class('form-control')->attribute('v-model', 'formData.phone')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.phone}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.phone">@{{errors.phone[0]}}
            </div>
        </div>

        <div class="form-group">
            {{ html()->label(__('Fax'), 'fax') }}
            {{ html()->text('fax')->id('fax')->class('form-control')->attribute('v-model', 'formData.fax')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.fax}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.fax">@{{errors.fax[0]}}
            </div>
        </div>

        <div class="form-group">
            {{ html()->label(__('Cell'), 'cell') }}
            {{ html()->text('cell')->id('cell')->class('form-control')->attribute('v-model', 'formData.cell')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.cell}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.cell">@{{errors.cell[0]}}
            </div>
        </div>
    <hr>
    <h5 class="mt-1 mb-3">{{__('Address')}}</h5>
    <div class="row">
        <div class="form-group col">
            {{ html()->label(__('Country'), 'country') }}
            <b-form-select id="country" v-model="formData.country" :options="countries" placeholder="Select" class="form-control">
                <template slot="first">
                    <option :value="null" disabled>{{__('Select')}}</option>
                </template>
            </b-form-select>
            <div class="invalid-feedback" role="alert" v-if="errors.country">
                @{{errors.country}}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col">
            {{ html()->label(__('Address'), 'address') }}
            {{ html()->text('address')->id('address')->class('form-control')->attribute('v-model', 'formData.address')->attribute('v-bind:class', '{\'form-control\':true,
            \'is-invalid\':errors.address}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.address">
                @{{errors.address}}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col">
            {{ html()->label(__('City'), 'city') }}
            {{ html()->text('city')->id('city')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.city')->attribute('v-bind:class', '{\'form-control\':true,
            \'is-invalid\':errors.city}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.city">@{{errors.city}}</div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col" v-if="formData.country == 'US'">
            {{ html()->label(__('State or Region'), 'state') }}
            <b-form-select v-model="formData.state" :options="states" placeholder="Select" class="form-control">
                <template slot="first">
                    <option :value="null" disabled>{{__('Select')}}</option>
                </template>
            </b-form-select>
            <div class="invalid-feedback" role="alert" v-if="errors.state">@{{errors.state}}
            </div>
        </div>
        <div class="form-group col" v-else>
            {{ html()->label(__('State or Region'), 'state') }}
            {{ html()->text('state')->id('state')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.state')->attribute('v-bind:class', '{\'form-control\':true,
            \'is-invalid\':errors.state}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.state">@{{errors.state}}
            </div>
        </div>
        <div class="form-group col">
            {{ html()->label(__('Postal Code'), 'postal') }}
            {{ html()->text('postal')->id('postal')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.postal')->attribute('v-bind:class', '{\'form-control\':true,
            \'is-invalid\':errors.postal}') }}
            <div class="invalid-feedback" role="alert" v-if="errors.postal">@{{errors.postal}}
            </div>
        </div>
    </div>
    @if (config('users.properties') && !\Request::is('profile/edit'))
        <hr>
        <h5 class="mt-1 mb-3">{{__('Additional Information')}}</h5>
        @foreach (config('users.properties') as $variable => $label)
            <div class="row">
                <div class="form-group col">
                    {{ html()->label(__($label), "meta.{$variable}") }}
                    {{ html()->text("meta.{$variable}")->id("meta.{$variable}")->class('form-control')->attribute('v-model', "formData.meta.{$variable}")->attribute('v-bind:class', '{\'form-control\':true}') }}
                </div>
            </div>
        @endforeach
    @endif
</div>

<div class="modal fade" id="validateModal" tabindex="-1" role="dialog" aria-labelledby="modalValidate" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">{{__('Confirm Identity')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
            <div class="form-group col">
                {{ html()->label(__('Password'), 'valpassword') }}
                <div style="position: relative;">
                    {{ html()->password('valpassword')->id('valpassword')->attribute('rows', 4)->class('form-control')->attribute('v-model', 'formData.valpassword') }}
                    <i class="fa fa-eye" id="togglePassword" style="position: absolute; top: 32%; right: 4%; cursor: pointer; color: #51585E;"></i>
                </div>
            </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
        <button type="button" class="btn btn-primary" @click="saveProfileChanges">Save</button>
      </div>
    </div>
  </div>
</div>

