@extends('layouts.layoutnextvite',['content_margin' => '', 'overflow-auto' => ''])

@section('title')
  {{ __('Case Detail') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_cases')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
      __('Cases') => route('cases-main.index'),
      $request->case_title . ' #' . $request->case_number => null,
  ]])
@endsection

@section('meta')
  <meta name="request-id" content="{{ $request->getKey() }}">
@endsection

@php
  $permissions = [
      'overviewTabTask' => Auth::user()->can('overview-tab-task'),
      'summaryTabTask' => Auth::user()->can('summary-tab-task'),
      'completedTabTask' => Auth::user()->can('completed-tab-task'),
      'formTabTask' => Auth::user()->can('form-tab-task'),
      'filesTabTask' => Auth::user()->can('files-tab-task'),
  ];
@endphp

@section('content')
<div
  id="case-detail"
  class="tw-p-4 tw-flex tw-overflow-hidden tw-space-x-2 tw-grow tw-h-full"
  v-cloak
>
    <case-detail
      class="tw-overflow-hidden tw-border tw-border-gray-200 tw-shadow-md tw-px-3 tw-flex-1 tw-bg-white tw-rounded-2xl"
      :permissions='@json($permissions)'
      v-cloak>
    </case-detail>
    <collapsable-container
      v-model="collapseContainer"
      class="tw-w-80"
      @change="onToogleContainer"
    >
      <template>
        <div class="tw-w-full tw-flex tw-border tw-border-gray-200 tw-shadow-md tw-px-3
          tw-bg-white tw-rounded-2xl">
            <Tabs
              class="tw-w-full"
              :tab-default="tabDefault"
              :tabs="tabs"
            >
              <template #details data-test="case-details-tab">
                <ul class="tw-w-full tw-space-y-3 tw-py-3">
                  @if ($canCancel == true && $request->status === 'ACTIVE')
                    <li class="tw-flex tw-items-center tw-justify-center">
                      <button
                        type="button"
                        class="tw-flex tw-justify-center tw-items-center tw-gap-2
                          tw-w-full tw-border tw-border-solid tw-border-gray-300 tw-px-3 tw-py-2 tw-shadow-sm tw-rounded-md tw-text-gray-600
                          tw-bg-gradient-to-b tw-from-white tw-to-transparent tw-via-white/5 tw-bg-gray-50"
                        aria-haspopup="dialog"
                        @click="onCancel"
                        data-test="cancel-case-button"
                      >
                        <i class="fas fa-ban"></i>
                        <span>@{{ $t('Cancel Case') }}</span>
                      </button>
                    </li>
                  @endif
                  <div :class="classStatusCard" data-test="case-status">
                    <span style="margin:0; padding:0; line-height:1">@{{ $t(statusLabel) }}</span>
                  </div>
                  <div>
                    <stage-bar
                      :title="stageName"
                      :percentage="progressStage"
                    />
                  </div>
                  <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300" data-test="case-since-date">
                    <p class="section-title">@{{ $t(labelDate) }}:</p>
                    <i class="far fa-calendar-alt"></i>
                    <small>@{{ moment(statusDate).format() }}</small>
                  </li>
                  @if ($request->user_id)
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300" data-test="case-startedby">
                      <p class="section-title">@{{ $t('STARTED BY') }}:</p>
                      <avatar-image
                        v-if="userRequested"
                        size="32"
                        class="d-inline-flex pull-left align-items-center"
                        :input-data="requestBy"
                        display-name="true"
                      ></avatar-image>
                      <span v-if="!userRequested">@{{ $t('Web Entry') }}</span>
                    </li>
                  @endif
                  <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300" data-test="case-launchpad">
                    <p class="section-title">@{{ $t('LAUNCHPAD') }}</p>
                    <p class="launchpad-link">
                      <a href="{{route('process.browser.index', [$request->process_id])}}">
                        {{ $request->name }}
                      </a>
                    </p>
                  </li>
                  @if ($request->participants->count())
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300" data-test="case-participants">
                      <p class="section-title">@{{ $t('PARTICIPANTS') }}:</p>
                      <avatar-image
                        size="32"
                        class="d-inline-flex pull-left align-items-center"
                        :input-data="participants"
                        display-name="true"
                        :vertical="true"
                      ></avatar-image>
                    </li>
                  @endif
                </ul>
              </template>
              <template #comments data-test="case-comments-tab">
                <comment-container
                  class="tw-grow tw-overflow-hidden"
                  :commentable_id="request.id"
                  commentable_type="{{ get_class($request) }}"
                  name="{{ $request->name }}"
                  :readonly="request.status === 'COMPLETED'"
                  :get-data="getCommentsData"
                  :is-case="true"
                  :case_number="{{ $request->case_number }}"
                />
              </template>
            </Tabs>
        </div>
      </template>
    </collapsable-container>
</div>
@endsection

@section('js')
  {{-- temporal must exist before loader (Vite modules run after deferred classic scripts) --}}
  <script>
    window.temporal = {};
    const requestId = @json($request->getKey());
    const request = @json($request->getRequestAsArray());

    const errorLogs = @json(['data' => $request->getErrors()]);
    const processId = @json($request->process->id);
    const canViewComments = @json($canViewComments);
    const comentable_type = @json(get_class($request));
    const requestCount = @json($requestCount);
    const screenBuilderScripts = @json($manager->getScripts());
    const inflightData = @json($inflightData);
    const currentStages = @json($currentStages);
    const progressStage = @json($progressStage);
    const tceCustomizationEnable = @json($isTceCustomization);

    temporal.requestId = requestId;
    temporal.request = request;
    temporal.canCancel = @json($canCancel);
    temporal.canViewPrint = @json($canPrintScreens);
    temporal.data = @json($request->getRequestData());
    temporal.bpmn = @json($bpmn);
    temporal.requestFiles = @json($request->requestFiles());
    temporal.pmBlockList = @json($pmBlockList);
    console.log(temporal);
    window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  </script>
  @vite(['resources/jscomposition/cases/casesDetail/loaderCasesDetail.js'])
  @vite(['resources/js/initialLoad.js'])

  @foreach(GlobalScripts::getScripts() as $script)
    <script defer src="{{$script}}"></script>
  @endforeach

  @foreach($managerModelerScripts as $params)
    <script defer
    @foreach ($params as $key => $value)
      @if (is_bool($value))
        {{ $key }}
      @else
        {{ $key }}="{{ $value }}"
      @endif
    @endforeach
    ></script>
  @endforeach

  @if (hasPackage('package-files'))
  <script defer src="{{ mix('js/manager-cases.js', 'vendor/processmaker/packages/package-files') }}"></script>
  @endif

  @vite(['resources/jscomposition/cases/casesDetail/casesDetail.js'])
@endsection

@section('css')
<style scoped>
  .active-style {
    background-color: #4ea075;
  }
  .canceled-style {
    background-color: #ed4858;
  }
  .text-status {
    display: flex;
    height: 48px;
    padding: 12px 16px;
    align-items: center;
    gap: 16px;
    color: white;
    border-radius: 6px;
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
    font-weight: 700;
    line-height: 22px;
    letter-spacing: -0.02em;
    text-align: left;
    text-transform: uppercase;
  }
  .section-title {
    color: var(--text-only, #556271);
    font-size: 14px;
    font-style: normal;
    font-weight: 700;
    line-height: 150%;
    letter-spacing: -0.28px;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
  }
</style>
@endsection
