<div class="form-group">
    {!! Form::label('name', 'Name') !!}
    {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
    <small id="emailHelp" class="form-text text-muted">Group name must be distinct</small>
    <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
</div>
<div class="form-group">
    {!! Form::label('description', 'Description') !!}
    {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control', 'v-model' => 'formData.description', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}']) !!}
    <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
</div>
<div class="form-group">
    {!! Form::label('status', 'Status') !!}
    {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null, ['id' => 'status', 'class' => 'form-control', 'v-model' => 'formData.status', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}']) !!}
    <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
</div>
