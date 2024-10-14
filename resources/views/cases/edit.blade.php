@extends('layouts.layout',['content_margin' => '', 'overflow-auto' => ''])

@section('title')
  {{ __('Case Detail') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_cases')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
      __('Cases') => route('cases.index'),
      $request->case_title . ' #' . $request->case_number => null,
  ]])
@endsection

@section('content')
<div id="case-detail" class="tw-p-4 tw-flex tw-overflow-hidden tw-space-x-2 tw-grow tw-h-full">
    <case-detail class="tw-overflow-hidden tw-border tw-border-gray-200 tw-shadow-md tw-px-3
      tw-flex-1 tw-bg-white tw-rounded-2xl">
    </case-detail>

    <collapsable-container class="tw-w-80" @change="onToogleContainer" v-model="collapseContainer">
      <template>
        <div class="tw-w-full tw-flex tw-border tw-border-gray-200 tw-shadow-md tw-px-3
          tw-bg-white tw-rounded-2xl">
            <Tabs
              class="tw-w-full"
              :tab-default="tabDefault"
              :tabs="tabs" >

              <template #details>
                <ul class="tw-w-full tw-space-y-3 tw-py-3 tw-text-gray-500 tw-overflow-y-scroll">
                  @if ($canCancel == true && $request->status === 'ACTIVE')
                    <li class="tw-flex tw-items-center tw-justify-center">
                      <button type="button" class="tw-w-full tw-border tw-border-gray-300 tw-px-3 tw-py-2
                        tw-shadow-sm tw-rounded-md" @click="onCancel" aria-haspopup="dialog">
                        <i class="fas fa-ban"></i>
                        <span>{{ __('Cancel Request') }}</span>
                      </button>
                    </li>
                  @endif

                  <div :class="classStatusCard">
                    <span style="margin:0; padding:0; line-height:1">@{{ __(statusLabel) }}</span>
                  </div>

                  <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                    <p class="section-title">@{{ __(labelDate) }}:</p>
                    <i class="far fa-calendar-alt"></i>
                    <small>@{{ moment(statusDate).format() }}</small>
                  </li>

                  @if ($request->user_id)
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                      <p class="section-title">{{ __('STARTED BY') }}:</p>
                      <avatar-image
                        v-if="userRequested"
                        size="32"
                        class="d-inline-flex pull-left align-items-center"
                        :input-data="requestBy"
                        display-name="true"
                      ></avatar-image>
                      <span v-if="!userRequested">{{ __('Web Entry') }}</span>
                    </li>
                  @endif

                  <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                    <p class="section-title">{{ __('LAUNCHPAD') }}</p>
                    <p class="launchpad-link">
                      <a href="{{route('process.browser.index', [$request->process_id])}}">
                        {{ $request->name }}
                      </a>
                    </p>
                  </li>
                  
                  @if ($request->participants->count())
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                      <p class="section-title">{{ __('PARTICIPANTS') }}:</p>
                      <avatar-image
                        size="32"
                        class="d-inline-flex pull-left align-items-center"
                        :input-data="participants"
                        hide-name="true"
                      ></avatar-image>
                    </li>
                  @endif
                </ul>
              </template>

              <template #comments v-if="panCommentInVueOptionsComponents">
                <comment-container
                  class="tw-grow tw-overflow-hidden"
                  :commentable_id="request.id"
                  commentable_type="{{ get_class($request) }}"
                  name="{{ $request->name }}"
                  :readonly="request.status === 'COMPLETED'"
                />
              </template>
            </Tabs>
        </div>
      </template>
    </collapsable-container>

</div>
@endsection

@section('js')
  <script>
    const data = @json($request->getRequestData());
    const requestId = @json($request->getKey());
    const request = @json($request->getRequestAsArray());
    const canCancel = @json($canCancel);
    const canViewPrint = @json($canPrintScreens);
    const errorLogs = @json(['data' => $request->getErrors()]);
    const processId = @json($request->process->id);
    const canViewComments = @json($canViewComments);
    const comentable_type = @json(get_class($request));
  </script>
  <script src="{{mix('js/composition/cases/casesDetail/edit.js')}}"></script>
@endsection
