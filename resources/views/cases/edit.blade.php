@extends('layouts.layout')

@section('title')
  {{ __('Case Detail') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
      __('Cases') => route('cases.index'),
  ]])
@endsection

@section('content')
<div id="case-detail" class="containe-fluid mr-3 ml-3 px-3 bg-light">
  <div class="d-flex flex-column flex-md-row">
    <div class="flex-grow-1 mr-3">
      <case-detail></case-detail>
    </div>
    <div>
      <template v-if="">
        <button
          role="button"
          class="btn d-block mr-0 ml-auto button-collapse"
          data-toggle="collapse"
          data-target="#collapse-info"
          @click="showTabs = !showTabs"
        >
          <template v-if="showTabs">
            <i class="fas fa-angle-right"></i>
          </template>
          <template v-else>
            <i class="fas fa-angle-left"></i>
          </template>
        </button>
        <ul v-if="showTabs" class="nav nav-tabs nav-collapse" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              id="details-tab"
              :class="{'nav-link': true, active: showInfo }"
              data-bs-toggle="tab"
              data-bs-target="#details"
              type="button"
              role="tab"
              aria-controls="details"
              aria-selected="true"
              @click="switchTabInfo('details')"
            >
              @{{ __('Details') }}
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              id="comments-tab"
              :class="{'nav-link': true, active: !showInfo }"
              data-bs-toggle="tab"
              data-bs-target="#comments"
              type="button"
              role="tab"
              aria-controls="comments"
              aria-selected="false"
              @click="switchTabInfo('comments')"
            >
              @{{ __('Comments') }}
            </button>
          </li>
        </ul>
      </template>
      <div class="tab-content">
        <div id="collapse-info" class="collapse show width">
          <div
            v-if="showInfo"
            id="details"
            v-bind:class="{ 'tab-pane':true, fade: true, show: showInfo, active: showInfo }"
            role="tabpanel"
            aria-labelledby="details-tab"
          >
          <div class="ml-md-3 mt-md-0 mt-3">
            <template v-if="">
              <div class="card collapse-content">
                <ul class="list-group list-group-flush w-100">
                  @if ($canCancel == true && $request->status === 'ACTIVE')
                    <li class="list-group-item">
                      <button type="button" class="btn btn-outline-custom btn-block" @click="onCancel" aria-haspopup="dialog">
                        {{ __('Cancel Request') }}
                      </button>
                    </li>
                  @endif
                  <div :class="classStatusCard">
                    <span style="margin:0; padding:0; line-height:1">@{{ __(statusLabel) }}</span>
                  </div>
                  @if ($request->participants->count())
                    <li class="list-group-item">
                      <p class="section-title">{{ __('Participants') }}:</p>
                      <avatar-image
                        size="32"
                        class="d-inline-flex pull-left align-items-center"
                        :input-data="participants"
                        hide-name="true"
                      ></avatar-image>
                    </li>
                  @endif
                  <li class="list-group-item">
                    <p class="section-title">@{{ __(labelDate) }}:</p>
                    <i class="far fa-calendar-alt"></i>
                    <small>@{{ moment(statusDate).format() }}</small>
                  </li>
                  <li class="list-group-item">
                    <p class="section-title">{{ __('Process') }}</p>
                    {{ $request->name }}
                    <p class="launchpad-link">
                      <a href="{{route('process.browser.index', [$request->process_id])}}">
                        {{ __('Open Process Launchpad') }}
                      </a>
                    </p>
                  </li>
                  @if ($request->user_id)
                    <li class="list-group-item">
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
                  @if ($canManuallyComplete == true)
                    <li class="list-group-item">
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
                    <li class="list-group-item">
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
                </div>
              </template>
            </div>
          </div>
          <div
            v-if="!showInfo"
            id="comments"
            v-bind:class="{ 'tab-pane':true, fade: true, show: !showInfo, active: !showInfo }"
            role="tabpanel"
            aria-labelledby="comments-tab"
          >
            <div class="ml-md-3 mt-md-0 mt-3 collapse-content">
              <template v-if="panCommentInVueOptionsComponents">
                <comment-container
                  :commentable_id="request.id"
                  commentable_type="{{ get_class($request) }}"
                  name="{{ $request->name }}"
                  :readonly="request.status === 'COMPLETED'"
                  :header="false"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
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
  </script>
  <script src="{{mix('js/composition/cases/casesDetail/edit.js')}}"></script>
@endsection
@section('css')
<style>
  .hidden {
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
  }
  .btn-outline-custom {
    border-radius: 4px;
    border: 1px solid var(--borders, #CDDDEE);
    background: linear-gradient(180deg, #fff 0%, rgb(255 255 255 / 0%) 100%), #fbfbfb;
    box-shadow: 0px 0px 3px -2px rgba(0, 0, 0, 0.10);
    color: var(--text-only, #556271);
  }
  .text-status {
    display: flex;
    height: 48px;
    padding: 12px 16px;
    align-items: center;
    gap: 16px;
    margin: 16px;
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
  .nav-collapse {
    padding: 0 16px;
    border: none;
  }
  .button-collapse {
    height: 32px;
    padding: 0 8px;
    border-radius: 4px;
    border: 1px solid var(--borders, #CDDDEE);
    background: var(--white-w24, #FFF);
    color: #6a7888;
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
  .collapse-content {
    min-width:0px;
    max-width:400px;
    width:317px;
    height:calc(100vh - 215px)
  }
  .active-style {
    background-color: #4ea075;
  }
  .canceled-style {
    background-color: #ed4858;
  }
  .card-header:first-child.text-status {
    border-radius: 6px;
  }
  .launchpad-link {
    margin-top: 5px;
  }
</style>
@endsection
