@extends('layouts.layout')

@section('title')
    {{__('Signals')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
		__('Designer') => route('processes.index'),
		__('Signals') => null,
	]])
@endsection

@section('content')
    <div class="px-3">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-item nav-link active" id="custom-signals-tab" data-toggle="tab" href="#nav-custom-signals" role="tab"
                   onclick="loadCustomSignals()" aria-controls="nav-custom-signals" aria-selected="true">
                    {{ __('Custom Signals') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-system-signals-tab" data-toggle="tab" href="#nav-system-signals" role="tab"
                   onclick="loadSystemSignals()" aria-controls="nav-system-signals" aria-selected="true">
                    {{ __('System Signals') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-collection-signals-tab" data-toggle="tab" href="#nav-collection-signals" role="tab"
                   onclick="loadCollectionSignals()" aria-controls="nav-collection-signals" aria-selected="true">
                    {{ __('Collection Signals') }}
                </a>
            </li>
        </ul>

        <div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="nav-custom-signals" role="tabpanel" aria-labelledby="custom-signals-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('processes.signals.listCustom')
                    </div>
                </div>

                <div class="tab-pane fade show" id="nav-system-signals" role="tabpanel" aria-labelledby="nav-system-signals-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('processes.signals.listSystem')
                    </div>
                </div>

                <div class="tab-pane fade show" id="nav-collection-signals" role="tabpanel" aria-labelledby="nav-collection-signals-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('processes.signals.listCollection')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    loadCustomSignals = function () {
        ProcessMaker.EventBus.$emit('api-data-custom-signals', true);
    };
    loadSystemSignals = function () {
        ProcessMaker.EventBus.$emit('api-data-system-signals', true);
    };
    loadCollectionSignals = function () {
        ProcessMaker.EventBus.$emit('api-data-collection-signals', true);
    };
</script>

@section('js')
    <script src="{{ mix('js/processes/signals/index.js') }}"></script>
    <script>
        new Vue({
            el: '#listCustomSignals',
            data() {
                return {
                    filter: '',
                    formData: {},
                    errors: {
                        'name': null,
                        'id': null,
                    },
                    disabled: false,
                };
            },
            mounted() {
                this.resetFormData();
                this.resetErrors();
            },
            methods: {
                onClose() {
                    this.resetFormData();
                    this.resetErrors();
                },
                resetFormData() {
                    this.formData = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                reload() {
                    this.$refs.signalCustomList.dataManager([
                        {
                            field: 'name',
                            direction: 'desc',
                        },
                    ]);
                    this.$refs.signalSystemList.dataManager([
                        {
                            field: 'name',
                            direction: 'desc',
                        },
                    ]);
                    this.$refs.signalCollectionList.dataManager([
                        {
                            field: 'name',
                            direction: 'desc',
                        },
                    ]);
                },
                onSubmit() {
                    this.resetErrors();
                    //single click
                    if (this.disabled) {
                        return;
                    }
                    this.disabled = true;
                    ProcessMaker.apiClient.post('signals', this.formData).then(response => {
                        ProcessMaker.alert("{{__('The signal was created.')}}", 'success');
                        //redirect list signal
                        window.location = '/designer/signals';
                    }).catch(error => {
                        this.disabled = false;
                        //define how display errors
                        if (error.response.status && error.response.status === 422) {
                            // Validation error
                            this.errors = error.response.data.errors;
                            //ProcessMaker.alert(this.errors, 'warning');
                        }
                    });
                },
            },
        });
    </script>
    <script>
        new Vue({
            el: '#listSystemSignals',
            data() {
                return {
                    filter: '',
                    formData: {},
                    errors: {
                        'name': null,
                        'id': null,
                    },
                    disabled: false,
                };
            },
            mounted() {
                this.resetFormData();
                this.resetErrors();
            },
            methods: {
                onClose() {
                    this.resetFormData();
                    this.resetErrors();
                },
                resetFormData() {
                    this.formData = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                reload() {
                    this.$refs.signalSystemList.dataManager([
                        {
                            field: 'name',
                            direction: 'desc',
                        },
                    ]);
                },
                onSubmit() {
                    this.resetErrors();
                    //single click
                    if (this.disabled) {
                        return;
                    }
                    this.disabled = true;
                    ProcessMaker.apiClient.post('signals', this.formData).then(response => {
                        ProcessMaker.alert("{{__('The signal was created.')}}", 'success');
                        //redirect list signal
                        window.location = '/designer/signals';
                    }).catch(error => {
                        this.disabled = false;
                        //define how display errors
                        if (error.response.status && error.response.status === 422) {
                            // Validation error
                            this.errors = error.response.data.errors;
                            //ProcessMaker.alert(this.errors, 'warning');
                        }
                    });
                },
            },
        });
    </script>
    <script>
        new Vue({
            el: '#listCollectionSignals',
            data() {
                return {
                    filter: '',
                    formData: {},
                    errors: {
                        'name': null,
                        'id': null,
                    },
                    disabled: false,
                };
            },
            mounted() {
                this.resetFormData();
                this.resetErrors();
            },
            methods: {
                onClose() {
                    this.resetFormData();
                    this.resetErrors();
                },
                resetFormData() {
                    this.formData = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                        name: null,
                        id: null,
                    });
                },
                reload() {
                    this.$refs.signalCollectionList.dataManager([
                        {
                            field: 'name',
                            direction: 'desc',
                        },
                    ]);
                },
                onSubmit() {
                    this.resetErrors();
                    //single click
                    if (this.disabled) {
                        return;
                    }
                    this.disabled = true;
                    ProcessMaker.apiClient.post('signals', this.formData).then(response => {
                        ProcessMaker.alert("{{__('The signal was created.')}}", 'success');
                        //redirect list signal
                        window.location = '/designer/signals';
                    }).catch(error => {
                        this.disabled = false;
                        //define how display errors
                        if (error.response.status && error.response.status === 422) {
                            // Validation error
                            this.errors = error.response.data.errors;
                            //ProcessMaker.alert(this.errors, 'warning');
                        }
                    });
                },
            },
        });
    </script>
@endsection
