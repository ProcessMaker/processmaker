@extends('layouts.mobile')
@section('title')
  {{ __('Request Detail') }}
@endsection
@section('content_mobile')
<div v-cloak id="requestMobile">
  <navbar-request-mobile :title="request.name"></navbar-request-mobile>
  <div class="d-flex flex-column" style="min-height: 100vh">
    <div class="flex-fill">
      <!-- Tabs navs -->
      <ul 
        id="ex1"
        class="nav nav-tabs nav-justified"
        role="tablist"
      >
        <li class="nav-item" role="presentation">
          <a
            id="pending-tab"
            class="nav-link active"
            data-toggle="tab"
            href="#pending"
            role="tab"
            aria-controls="pending"
            aria-selected="true"
            @click="switchTab('pending')" 
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
        class="tab-content"
        id="requestTabContent"
      >
        <div
          id="pending"
          class="tab-pane fade show card card-body border-top-0 p-0"
          role="tabpanel"
          aria-labelledby="pending-tab"
        >
          TASKS HERE
        </div>
        <div 
          id="summary"
          role="tabpanel"
          class="tab-pane card card-body border-top-0 p-3"
          aria-labelledby="summary-tab"
        >
          <template v-if="showSummary">
            <template v-if="showScreenSummary">
              <div class="p-3">
                <vue-form-renderer
                  v-model="dataSummary"
                  ref="screen"
                  :config="screenSummary.config"
                  :computed="screenSummary.computed" 
                />
              </div>
            </template>
            <template 
              v-if="showScreenRequestDetail && !showScreenSummary">
              <div class="card">
                <div class="card-body">
                  <vue-form-renderer ref="screenRequestDetail" :config="screenRequestDetail"
                    v-model="dataSummary" />
                </div>
              </div>
            </template>
            <template v-if="!showScreenSummary && !showScreenRequestDetail">
              <template v-if="summary.length > 0">
                <template v-if="!activePending">
                  <p class="lead font-weight-bold">
                    {{ __('Request Completed') }}
                  </p>
                  <div class="card border-1 scroll">
                    <data-summary :summary="dataSummary"></data-summary>
                  </div>
                  @can('view-comments')
                    <timeline commentable_id="{{ $request->getKey() }}" commentable_type="{{ get_class($request) }}"
                      :reactions="configurationComments.reactions" :voting="configurationComments.voting"
                      :edit="configurationComments.edit" :remove="configurationComments.remove"
                      :adding="configurationComments.comments" :readonly="request.status === 'COMPLETED'" />
                  @endcan
                </template>
                <template v-else>
                  <div class="justify-content-center align-self-center bg-white p-5">
                        <p class="lead font-weight-bold text-center">
                          {{ __('Request In Progress') }}
                        </p>
                        <p class="text-center font-weight-light">
                          {{ __('This Request is currently in progress.') }}
                          {{ __('This screen will be populated once the Request is completed.') }}
                        </p>
                  </div>
                </template>
              </template>
              <template v-else>
                <div class="card border-0">
                  <div class="card-header bg-white">
                    <h5 class="m-0">
                      {{ __('No Data Found') }}
                    </h5>
                  </div>

                  <div class="card-body">
                    <p class="card-text">
                      {{ __("Sorry, this request doesn't contain any information.") }}
                    </p>
                  </div>
                </div>
              </template>

            </template>
          </template>
        </div>

        <div class="tab-pane fade card card-body border-top-0 p-3" id="files" role="tabpanel"
          aria-labelledby="files-tab">
          FILES HERE
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
  <script src="{{ mix('js/requests/show.js') }}"></script>

  <script>
    const main = new Vue({
      el: "#requestMobile",
      mixins: addons,
      data() {
        return {
          canViewComments: @json($canViewComments),
          request: @json($request),
          configurationComments: {
            comments: false,
            reactions: false,
            edit: false,
            voting: false,
            remove: false,
          },
        }
      },
      computed: {
        activePending() {
          return this.request.status === 'ACTIVE';
        },
        /**
         * Request Summary - that is blank place holder if there are in progress tasks,
         * if the request is completed it will show key value pairs.
         *
         */
        showSummary() {
          return this.request.status === 'ACTIVE' || this.request.status === 'COMPLETED' || this.request.status ===
            'CANCELED';
        },
        /**
         * If the screen summary is configured.
         **/
        showScreenSummary() {
          return this.request.summary_screen !== null;
        },
        /**
         * Get the summary of the Request.
         *
         */
        summary() {
          return this.request.summary;
        },
        /**
         * Get Screen summary
         * */
        screenSummary() {
          return this.request.summary_screen;
        },
        /**
         * prepare data screen
         **/
        dataSummary() {
          let options = {};
          this.request.summary.forEach(option => {
            options[option.key] = option.value;
          });
          return options;
        },
        /**
         * If the screen request detail is configured.
         **/
        showScreenRequestDetail() {
          return !!this.request.request_detail_screen;
        },
        /**
         * Get Screen request detail
         * */
        screenRequestDetail() {
          return this.request.request_detail_screen ? this.request.request_detail_screen.config : null;
        },
      },
      mounted() {
        this.getConfigurationComments();
      },
      methods: {
        switchTab(tab) {
          this.activeTab = tab;
          if (tab === 'overview') {
            this.iframeLoading = true;
          }
          ProcessMaker.EventBus.$emit('tab-switched', tab);
        },
        getConfigurationComments() {
          if (this.canViewComments) {
            const commentsPackage = 'comment-editor' in Vue.options.components;
            if (commentsPackage) {
              ProcessMaker.apiClient.get(`comments/configuration`, {
                params: {
                  id: this.processId,
                  type: 'Process',
                },
              }).then(response => {
                this.configurationComments.comments = !!response.data.comments;
                this.configurationComments.reactions = !!response.data.reactions;
                this.configurationComments.voting = !!response.data.voting;
                this.configurationComments.edit = !!response.data.edit;
                this.configurationComments.remove = !!response.data.remove;
              });
            }
          }
        },
      },
    });
  </script>
@endsection

@section('css')

@endsection
