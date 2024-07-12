@extends('layouts.preview')


@section('content')
    <div id="sidebar" style="display: none"></div>
    <div id="navbar" style="display: none"></div>
    <div class="container" id="completedScreen">
        <div class="row">
            <div class="col-12">
                <screen-preview
                    :screen="formData"
                />
            </div>
        </div>
    </div>
@endsection

@section('js')
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screens/preview.js')}}"></script>
    <script>
        new Vue({
            el: '#completedScreen',
            data() {
                return {
                    formData: @json($customScreen),
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
