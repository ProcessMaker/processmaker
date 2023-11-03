@extends('layouts.preview')


@section('content')
    <div class="container" id="script-container">
        <div class="row">
            <div class="col-12 preview-item-title">
                {{ $script->title }}
            </div>
        </div>
        <div class="row h-100">
            <div class="col-12">
                <script-preview
                        :script="{{ $script }}"
                        :script-executor='{!! json_encode($script->scriptExecutor) !!}'
                        test-data="{{ json_encode($testData, JSON_PRETTY_PRINT) }}"
                        :auto-save-delay="{{ $autoSaveDelay }}"
                        :is-versions-installed="@json($isVersionsInstalled)"
                        :is-draft="@json($isDraft)"
                        :package-ai="{{ hasPackage('package-ai') ? 1 : 0 }}"
                        :user="{{ $user }}"
                >
                </script-preview>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        div.main {
            position: relative;
        }

        #script-container {
            position: absolute;
            width: 100%;
            max-width: 100%;
            height: 90%;
            max-height: 100%;
            overflow: hidden;
        }
    </style>
@endsection

@section('js')
    <script src="{{mix('js/processes/scripts/preview.js')}}"></script>
@endsection
