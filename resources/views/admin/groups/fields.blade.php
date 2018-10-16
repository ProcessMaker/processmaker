<div class="form-group">
    {!! Form::label('name', 'Name') !!}
    {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name']) !!}
    <div class="invalid-feedback">Example invalid feedback text</div>
    <small id="emailHelp" class="form-text text-muted">Username must be distinct</small>
</div>
<div class="form-group">
    {!! Form::label('description', 'Description') !!}
    {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control', 'v-model' => 'formData.description']) !!}
</div>
<div class="form-group">
    {!! Form::label('status', 'Status') !!}
    {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null, ['id' => 'status', 'class' => 'form-control', 'v-model' => 'formData.status']) !!}
</div>
