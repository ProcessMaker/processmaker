<div class="card card-body">
    <h5 class="mb-3 font-weight-bold">{{__('Profile')}}</h5>
    <h5 class="mb-3">{{__('General Information')}}</h5>
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
            {!! Form::label('email', __('Email')) !!}
            {!! Form::email('email', null, ['id' => 'email', 'rows' => 4, 'class'=>
            'form-control', 'v-model'
            => 'formData.email', 'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.email}']) !!}
            <div class="invalid-feedback" v-if="errors.email">@{{errors.email[0]}}
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('phone', __('Phone')) !!}
            {!! Form::text('phone', null, ['id' => 'phone','class'=> 'form-control',
            'v-model' => 'formData.phone',
            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.phone}'])
            !!}
            <div class="invalid-feedback" v-if="errors.phone">@{{errors.phone}}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('fax', __('Fax')) !!}
            {!! Form::text('fax', null, ['id' => 'fax','class'=> 'form-control',
            'v-model' => 'formData.fax',
            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.fax}'])
            !!}
            <div class="invalid-feedback" v-if="errors.fax">@{{errors.fax}}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('cell', __('Cell')) !!}
            {!! Form::text('cell', null, ['id' => 'cell','class'=> 'form-control',
            'v-model' => 'formData.cell',
            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.cell}'])
            !!}
            <div class="invalid-feedback" v-if="errors.cell">@{{errors.cell}}
            </div>
        </div>
<hr>
    <h5 class="mt-1 mb-3">{{__('Address')}}</h5>
    <div class="row">
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
    </div>
    <div class="row">
        <div class="form-group col" v-if="formData.country == 'US'">
            {!! Form::label('state', __('State or Region')) !!}
            <b-form-select v-model="formData.state" :options="states" placeholder="Select" class="form-control">
                <template slot="first">
                    <option :value="null" disabled>{{__('Select')}}</option>
                </template>
            </b-form-select>
            <div class="invalid-feedback" v-if="errors.state">@{{errors.state}}
            </div>
        </div>
        <div class="form-group col" v-else>
            {!! Form::label('state', __('State or Region')) !!}
            {!! Form::text('state', null, ['id' => 'state', 'rows' => 4, 'class'=>
            'form-control', 'v-model'
            => 'formData.state', 'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.state}']) !!}
            <div class="invalid-feedback" v-if="errors.state">@{{errors.state}}
            </div>
        </div>
        <div class="form-group col">
            {!! Form::label('postal', __('Postal Code')) !!}
            {!! Form::text('postal', null, ['id' => 'postal', 'rows' => 4, 'class'=>
            'form-control', 'v-model'
            => 'formData.postal', 'v-bind:class' => '{\'form-control\':true,
            \'is-invalid\':errors.postal}']) !!}
            <div class="invalid-feedback" v-if="errors.postal">@{{errors.postal}}
            </div>
        </div>
    </div>
</div>