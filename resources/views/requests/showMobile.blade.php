@extends('layouts.mobile')
@section('title')
  {{ __('Request Detail') }}
@endsection
@section('content_mobile')
<div v-cloak id="requestMobile">
  <navbar-request-mobile :title="request.name"></navbar-request-mobile>
  <!--Header-->
  @if (shouldShow('requestStatusContainer'))
  @php
    $values = [
      'canCancel' => "$canCancel",
      'canManuallyComplete' => "$canManuallyComplete",
      'canRetry' => "$canRetry",
      'eligibleRollbackTask' => "$eligibleRollbackTask",
      'errorTask' => "$errorTask"
    ]
  @endphp
    <request-header-mobile
      :request="request"
      values="{{ json_encode($values)}}"
    >
    </request-header-mobile>
  @endif
  <div class="d-flex flex-column" style="min-height: 100vh">
    <div class="flex-fill">
      <!-- Tabs navs -->
      <ul 
        id="tabsRequests"
        class="nav nav-tabs nav-justified"
        role="tablist"
      >
        <li class="nav-item" role="presentation">
          <a
            id="tasks-tab"
            class="nav-link active"
            data-toggle="tab"
            href="#tasks"
            role="tab"
            aria-controls="tasks"
            aria-selected="true"
            @click="switchTab('tasks')" 
          >
            {{ __('Tasks') }}
          </a
          >
        </li>
        <li class="nav-item" role="presentation">
          <a
            id="summary-tab"
            class="nav-link"
            data-toggle="tab"
            href="#summary"
            role="tab"
            aria-controls="summary"
            aria-selected="false"
            @click="switchTab('summary')" 
          >
            {{ __('Summary') }}
          </a
          >
        </li>
        @if (count($files) > 0 && !hasPackage('package-files'))
          <li class="nav-item" role="presentation">
            <a
              id="files-tab"
              class="nav-link"
              data-toggle="tab"
              href="#files"
              role="tab"
              aria-controls="files"
              aria-selected="false"
              @click="switchTab('files')" 
            >
              {{ __('File Manager') }}
            </a>
          </li>
        @endif
      </ul>
      <div 
        id="requestTabContent"
        class="tab-content"
      >
        <div
          id="tasks"
          class="tab-pane fade show card card-body border-top-0 p-3 active"
          role="tabpanel"
          aria-labelledby="tasks-tab"
        >
          <request-detail-mobile
            ref="pending"
            :process-request-id="request.id"
            :is-process-manager="{{ $isProcessManager ? 'true' : 'false' }}"
            :is-admin="{{ Auth::user()->is_administrator ? 'true' : 'false' }}">
          </request-detail-mobile>
        </div>
        <div 
          id="summary"
          role="tabpanel"
          class="tab-pane card card-body border-top-0 p-3"
          aria-labelledby="summary-tab"
        >
          <summary-mobile 
            :request="request"
            :canViewComments="canViewComments"
            v-bind:permission="{{ \Auth::user()->hasPermissionsFor('comments') }}"
          >
          </summary-mobile>
        </div>

        <div 
          id="files" 
          class="tab-pane fade card card-body border-top-0 p-3" 
          role="tabpanel"
          aria-labelledby="files-tab"
        >
          @php
            $arrayFiles = [];
            foreach ($files as $file) {
              $arrayFiles[] = $file; 
            }
            $jsonFiles = json_encode($arrayFiles);
          @endphp

          <files-mobile
            :request="request"
            files="{{ $jsonFiles }}"
          >
          </files-mobile>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
  .img-under {
    width: 100%;
  }
  .scroll {
    max-height: 241px;
    overflow-y: auto;
  }
</style>
@endsection

@section('js')
  @if (hasPackage('package-files'))
    <script src="{{ mix('js/manager.js', 'vendor/processmaker/packages/package-files') }}"></script>
  @endif

  <script>
    window.PM4ConfigOverrides = {
      requestFiles: @json($request->requestFiles())
    };
  </script>

  <script src="{{ mix('js/requests/show.js') }}"></script>

  <script>
    const main = new Vue({
      el: "#requestMobile",
      mixins: addons,
      data() {
        return {
          canViewComments: @json($canViewComments),
          request: @json($request),
          files: @json($files),
          canCancel: @json($canCancel),
          canManuallyComplete: @json($canManuallyComplete),
          canRetry: @json($canRetry),
          errorTask: @json($errorTask),
          eligibleRollbackTask: @json($eligibleRollbackTask),
        }
      },
      methods: {
        switchTab(tab) {
          this.activeTab = tab;
          if (tab === 'overview') {
            this.iframeLoading = true;
          }
          ProcessMaker.EventBus.$emit('tab-switched', tab);
        },
        getFiles(files) {
          return json_decode(files)
        }
      },
    });
  </script>
@endsection

@section('css')

@endsection
