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
  ]])
@endsection

@section('content')
<div id="case-detail" class="tw-p-4 tw-flex tw-overflow-hidden tw-space-x-2 tw-grow tw-h-full">
    <case-detail class="tw-overflow-hidden tw-border tw-border-gray-200 tw-shadow-md tw-px-3
      tw-flex-1 tw-bg-white tw-rounded-2xl">
    </case-detail>

    <collapsable-container class="tw-w-80">
      <template>
        <div class="tw-w-full tw-flex tw-border tw-border-gray-200 tw-shadow-md tw-px-3
          tw-bg-white tw-rounded-2xl">
            <Tabs
              class="tw-w-full"
              :tab-default="tabDefault"
              :tabs="tabs" >

              <template #details>
                <ul class="tw-w-full tw-space-y-3 tw-py-3 tw-text-gray-500">
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
                      <p class="section-title">{{ __('Requested By') }}:</p>
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
                    <p class="section-title">{{ __('Process') }}</p>
                    {{ $request->name }}
                    <p class="launchpad-link">
                      <a href="{{route('process.browser.index', [$request->process_id])}}">
                        {{ __('Open Process Launchpad') }}
                      </a>
                    </p>
                  </li>
                  
                  @if ($request->participants->count())
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                      <p class="section-title">{{ __('Participants') }}:</p>
                      <avatar-image
                        size="32"
                        class="d-inline-flex pull-left align-items-center"
                        :input-data="participants"
                        hide-name="true"
                      ></avatar-image>
                    </li>
                  @endif

                  @if ($canManuallyComplete == true)
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                      <p class="section-title">{{ __('Manually Complete Request') }}</p>
                      <button
                        type="button"
                        class="btn btn-outline-success btn-block"
                        data-toggle="modal"
                        @click="completeRequest"
                      >
                        <i class="fas fa-stop-circle"></i> {{ __('Complete') }}
                      </button>
                    </li>
                  @endif
                  @if ($canRetry === true)
                    <li class="tw-px-4 tw-py-3 tw-border-b tw-border-gray-300">
                      <p class="section-title">{{ __('Retry Request') }}</p>
                      <button id="retryRequestButton" type="button" class="btn btn-outline-info btn-block"
                        data-toggle="modal" :disabled="retryDisabled" @click="retryRequest">
                        <i class="fas fa-sync"></i> {{ __('Retry') }}
                      </button>
                    </li>
                  @endif
                  @if ($eligibleRollbackTask)
                    @can('rollback', $errorTask)
                      <li
                        v-if="{{ $isProcessManager ? 'true' : 'false' }} ||
                          {{ Auth::user()->is_administrator ? 'true' : 'false' }}"
                        class="list-group-item"
                      >
                        <p class="section-title">{{ __('Rollback Request') }}</p>
                          <button
                            id="retryRequestButton"
                            type="button"
                            class="btn btn-outline-info btn-block"
                            data-toggle="modal"
                            @click="rollback({{ $errorTask->id }}, '{{ $eligibleRollbackTask->element_name }}')"
                          >
                            <i class="fas fa-undo"></i> {{ __('Rollback') }}
                          </button>
                          <small>{{ __('Rollback to task') }}: <b>{{ $eligibleRollbackTask->element_name }}</b> ({{ $eligibleRollbackTask->element_id }})</small>
                        </li>
                      @endcan
                    @endif
                    @if ($request->parentRequest)
                      <li class="list-group-item">
                        <p class="section-title">{{ __('Parent Request') }}</p>
                        <i :class="requestStatusClass('{{ $request->parentRequest->status }}')"></i>
                        <a href="/requests/{{ $request->parentRequest->getKey() }}">{{ $request->parentRequest->name }}</a>
                      </li>
                    @endif
                    @if (count($request->childRequests))
                      <li class="list-group-item">
                        <p class="section-title">{{ __('Child Requests') }}</p>
                        @foreach ($request->childRequests as $childRequest)
                          <div>
                            <i :class="requestStatusClass('{{ $childRequest->status }}')"></i>
                            <a href="/requests/{{ $childRequest->getKey() }}">{{ $childRequest->name }}</a>
                          </div>
                        @endforeach
                      </li>
                    @endif
                </ul>
              </template>

              <template #comments v-if="panCommentInVueOptionsComponents">
                <comment-container
                  class="tw-grow tw-overflow-hidden"
                  :commentable_id="requestId"
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
    const files = @json($files);
    const canCancel = @json($canCancel);
    const canViewPrint = @json($canPrintScreens);
    const errorLogs = @json(['data' => $request->getErrors()]);
    const processId = @json($request->process->id);
    const canViewComments = @json($canViewComments);
    const comentable_type = @json(get_class($request));
  </script>
  <script src="{{mix('js/composition/cases/casesDetail/edit.js')}}"></script>
@endsection