<div id="listControllerSignals">
	<div class="px-3">
		<div id="search-bar" class="search mb-3" vcloak>
			<div class="d-flex flex-column flex-md-row">
				<div class="flex-grow-1">
					<div id="search" class="mb-3 mb-md-0">
						<div class="input-group w-100">
							<input id="search-box" v-model="filter" class="form-control"
								   placeholder="{{__('Search all controller signals')}}" aria-label="{{__('Search')}}">
							<div class="input-group-append">
								<button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i
											class="fas fa-search"></i></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="container-fluid">
			<controller-signals-listing ref="signalControllerList"
							 :filter="filter"
							 :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
							 v-on:reload="reload"></controller-signals-listing>
		</div>
	</div>

	@can('create-processes')
		<pm-modal ref="createSignal" id="createSignal" title="{{__('New Signal')}}" @hidden="onClose"
				  @ok.prevent="onSubmit" :ok-disabled="disabled" style="display: none;">
			<required></required>
			<div class="form-group">
				{!! Form::label('name', __('Signal Name') . '<small class="ml-1">*</small>', [], false) !!}
				{!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
				'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}', 'required', 'aria-required' => 'true']) !!}
				<div class="invalid-feedback" role="alert" v-for="name in errors.name">@{{name}}</div>
			</div>
			<div class="form-group">
				{!! Form::label('id', __('Signal ID') . '<small class="ml-1">*</small>', [], false) !!}
				{!! Form::text('id', null, ['id' => 'id','class'=> 'form-control', 'v-model' =>
				'formData.id', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.id}', 'required', 'aria-required' => 'true']) !!}
				<div class="invalid-feedback" role="alert" v-for="id in errors.id">@{{id}}</div>
			</div>
			<div class="form-group">
				{!! Form::textarea('detail', null, ['id' => 'detail', 'rows' => 4, 'class'=> 'form-control', 'v-bind:placeholder' => '$t("Additional Details (optional)")',
				'aria-label' => __('Additional Details (optional)'),
				'v-model' => 'formData.detail', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.detail}']) !!}
				<div class="invalid-feedback" role="alert" v-if="errors.detail">@{{errors.detail[0]}}</div>
			</div>
		</pm-modal>
	@endcan
</div>

