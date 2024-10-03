<div class="card card-body">
    <required></required>
    <h5 class="mb-3 font-weight-bold">{{__('Profile')}}</h5>
    <h5 class="mb-3">{{__('General Information')}}</h5>
        <div class="row">
            <div class="form-group col">
                {!! Form::label('firstname', __('First Name') . '<small class="ml-1">*</small>', [], false) !!}
                {!! Form::text('firstname', null, ['id' => 'firstname','class'=>
                'form-control', 'v-model' => 'formData.firstname',
                'v-bind:class' => '{\'form-control\':true,
                \'is-invalid\':errors.firstname}', 'required', 'aria-required' => 'true']) !!}
                <div class="invalid-feedback" role="alert" v-if="errors.firstname">
                    @{{errors.firstname[0]}}
                </div>
            </div>
            <div class="form-group col">
                {!! Form::label('lastname', __('Last Name') . '<small class="ml-1">*</small>', [], false)!!}
                {!! Form::text('lastname', null, ['id' => 'lastname', 'rows' => 4,
                'class'=> 'form-control', 'v-model'
                => 'formData.lastname', 'v-bind:class' => '{\'form-control\':true,
                \'is-invalid\':errors.lastname}', 'required', 'aria-required' => 'true']) !!}
                <div class="invalid-feedback" role="alert" v-if="errors.lastname">
                    @{{errors.lastname[0]}}
                </div>
            </div>
        </div>

        <div class="form-group">
            {!!Form::label('title', __('Job Title')) !!}
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
            {!! Form::label('email', __('Email') . '<small class="ml-1">*</small>', [], false) !!}
            {!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=>
            'form-control', 'v-model'
            => 'formData.email', 'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.email}', 'required', 'aria-required' => 'true']) !!}
            <div class="invalid-feedback" role="alert" v-if="errors.email">@{{errors.email[0]}}
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('phone', __('Phone')) !!}
            {!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control',
            'v-model' => 'formData.phone',
            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}'])
            !!}
            <div class="invalid-feedback" role="alert" v-if="errors.phone">@{{errors.phone[0]}}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('fax', __('Fax')) !!}
            {!! Form::text('fax', null, ['id' => 'fax','class'=> 'form-control',
            'v-model' => 'formData.fax',
            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.fax}'])
            !!}
            <div class="invalid-feedback" role="alert" v-if="errors.fax">@{{errors.fax[0]}}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('cell', __('Cell')) !!}
            {!! Form::text('cell', null, ['id' => 'cell','class'=> 'form-control',
            'v-model' => 'formData.cell',
            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.cell}'])
            !!}
            <div class="invalid-feedback" role="alert" v-if="errors.cell">@{{errors.cell[0]}}
            </div>
        </div>
    <hr>
    <h5 class="mt-1 mb-3">{{__('Address')}}</h5>
    <div class="row">
        <div class="form-group col">
            {!! Form::label('country', __('Country')) !!}
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
            {!! Form::label('address', __('Address')) !!}
            {!! Form::text('address', null, ['id' => 'address','class'=>
            'form-control', 'v-model' => 'formData.address',
            'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.address}']) !!}
            <div class="invalid-feedback" role="alert" v-if="errors.address">
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
            <div class="invalid-feedback" role="alert" v-if="errors.city">@{{errors.city}}</div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col" v-if="formData.country == 'US'">
            {!! Form::label('state', __('State or Region')) !!}
            <b-form-select v-model="formData.state" :options="states" placeholder="Select" class="form-control">
                <template slot="first">
                    <option :value="null" disabled>{{__('Select')}}</option>
                </template>
            </b-form-select>
            <div class="invalid-feedback" role="alert" v-if="errors.state">@{{errors.state}}
            </div>
        </div>
        <div class="form-group col" v-else>
            {!! Form::label('state', __('State or Region')) !!}
            {!! Form::text('state', null, ['id' => 'state', 'rows' => 4, 'class'=>
            'form-control', 'v-model'
            => 'formData.state', 'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.state}']) !!}
            <div class="invalid-feedback" role="alert" v-if="errors.state">@{{errors.state}}
            </div>
        </div>
        <div class="form-group col">
            {!! Form::label('postal', __('Postal Code')) !!}
            {!! Form::text('postal', null, ['id' => 'postal', 'rows' => 4, 'class'=>
            'form-control', 'v-model'
            => 'formData.postal', 'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.postal}']) !!}
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
                    {!! Form::label("meta.$variable", __($label)) !!}
                    {!! Form::text("meta.$variable", null, ['id' => "meta.$variable",'class'=>
                    'form-control', 'v-model' => "formData.meta.$variable",
                    'v-bind:class' => '{\'form-control\':true}']) !!}
                </div>
            </div>
        @endforeach
    @endif
</div>

<div class="modal fade" id="validateModal" tabindex="-1" role="dialog" aria-labelledby="modalValidate" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Confirm Identity</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
            <div class="form-group col">
                {!! Form::label('valpassword', __('Password')) !!}
                <div style="position: relative;">
                    {!! Form::password('valpassword', ['id' => 'valpassword', 'rows' => 4, 'class'=> 'form-control', 'v-model'
                            => 'formData.valpassword']) !!}
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

