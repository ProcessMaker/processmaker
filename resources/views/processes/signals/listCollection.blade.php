<div id="listCollectionSignals">
	<div class="px-3">
		<div id="search-bar" class="search mb-3" vcloak>
			<div class="d-flex flex-column flex-md-row">
				<div class="flex-grow-1">
					<div id="search" class="mb-3 mb-md-0">
						<div class="input-group w-100">
							<input id="search-box" v-model="filter" class="form-control"
								   placeholder="{{__('Search all collection signals')}}" aria-label="{{__('Search')}}">
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
			<collection-signals-listing ref="signalCollectionList"
							 :filter="filter"
							 :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
							 v-on:reload="reload"></collection-signals-listing>
		</div>
	</div>

