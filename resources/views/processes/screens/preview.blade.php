@extends('layouts.layout')


@section('content')
    <div class="container" id="screenPreview">
        <div class="row">
            <div class="col-12">
                <screen-detail
                    :can-print="false"
                    :row-data="formData"
                    :row-index="0"
                    :timeout-on-load="false"
                />
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/screens/preview.js')}}"></script>
    <script>
        new Vue({
            el: '#screenPreview',
            data() {
                return {
                    formData: @json($screen),
                    errors: {
                        'title': null,
                        'type': null,
                        'description': null,
                        'status': null
                    }
                }
            },
        });
    </script>
@endsection
