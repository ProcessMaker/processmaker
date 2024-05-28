<template v-if="showSummary">
  <div>
    <template v-if="showScreenSummary">
      <div class="p-3">
        <vue-form-renderer
          ref="screen"
          v-model="dataSummary"
          :config="screenSummary.config"
          :computed="screenSummary.computed"
        />
      </div>
    </template>
    <template v-if="showScreenRequestDetail && !showScreenSummary">
      <div class="card">
        <div class="card-body">
          <vue-form-renderer
            ref="screenRequestDetail"
            v-model="dataSummary"
            :config="screenRequestDetail"
          />
        </div>
      </div>
    </template>
    <template v-if="!showScreenSummary && !showScreenRequestDetail">
      <template v-if="summary.length > 0">
        <template v-if="!activePending">
          <p class="lead font-weight-bold">
            {{ $t("Request Completed") }}
          </p>
          <div class="card border-1 scroll">
            <data-summary :summary="dataSummary" />
          </div>
          <div v-if="permission.includes('view-comments')">
            <timeline
              :reactions="configurationComments.reactions"
              :voting="configurationComments.voting"
              :edit="configurationComments.edit"
              :remove="configurationComments.remove"
              :adding="configurationComments.comments"
              :readonly="request.status === 'COMPLETED'"
              :commentable_id="request.id"
              commentable_type="ProcessMaker\Models\ProcessRequest"
            />
          </div>
        </template>
        <template v-else>
          <div class="justify-content-center align-self-center bg-white p-5">
            <p class="lead font-weight-bold text-center">
              {{ $t("Request In Progress") }}
            </p>
            <p class="text-center font-weight-light">
              {{ $t("This Request is currently in progress.") }}
              {{ $t("This screen will be populated once the Request is completed.") }}
            </p>
          </div>
        </template>
      </template>
      <template v-else>
        <div class="justify-content-center align-self-center bg-white p-5">
          <p class="lead font-weight-bold text-center">
            {{ $t("No Data Found") }}
          </p>
          <p class="text-center font-weight-light">
            {{ $t("Sorry, this request doesn't contain any information.") }}
          </p>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
import Vue from "vue";
import VueFormRenderer from "@processmaker/screen-builder";
import DataSummary from "./DataSummary.vue";
import Timeline from "../../components/Timeline.vue";
import TimelineItem from "../../components/TimelineItem.vue";

Vue.component("DataSummary", DataSummary);
Vue.component("Timeline", Timeline);
Vue.component("TimelineItem", TimelineItem);
Vue.component("VueFormRenderer", VueFormRenderer);

export default {
  props: ["request", "canViewComments", "requestKey", "requestType", "permission"],
  data() {
    return {
      configurationComments: {
        comments: false,
        reactions: false,
        edit: false,
        voting: false,
        remove: false,
      },
      key: this.requestKey,
      type: this.requestType,
    };
  },
  computed: {
    activePending() {
      return this.request.status === "ACTIVE";
    },
    /**
     * Request Summary - that is blank place holder if there are in progress tasks,
     * if the request is completed it will show key value pairs.
     *
     */
    showSummary() {
      return this.request.status === "ACTIVE"
      || this.request.status === "COMPLETED"
      || this.request.status === "CANCELED";
    },
    /**
     * If the screen summary is configured.
     */
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
     */
    screenSummary() {
      return this.request.summary_screen;
    },
    /**
     * prepare data screen
     */
    dataSummary() {
      console.log('okok090');
      let options = {};
      this.request.summary.forEach((option) => {
        if (option.type === 'datetime') {
          options[option.key] = moment(option.value).
                  tz(window.ProcessMaker.user.timezone).
                  format("MM/DD/YYYY HH:mm");
        } else {
          options[option.key] = option.value;
        }
      });
      return options;
    },
    /**
     * If the screen request detail is configured.
     */
    showScreenRequestDetail() {
      return !!this.request.request_detail_screen;
    },
    /**
     * Get Screen request detail
     */
    screenRequestDetail() {
      return this.request.request_detail_screen ? this.request.request_detail_screen.config : null;
    },
  },
  mounted() {

    // Comments configuration no longer exists
    // this.getConfigurationComments();
  },
  methods: {
    getConfigurationComments() {
      if (this.canViewComments) {
        const commentsPackage = "comment-editor" in Vue.options.components;
        if (commentsPackage) {
          ProcessMaker.apiClient.get("comments/configuration", {
            params: {
              id: this.processId,
              type: "Process",
            },
          }).then((response) => {
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
};
</script>
