<template>
  <div id="start-events btn-group">
    <button
      v-if="havelessOneStartEvent"
      class="btn btn-success btn-lg start-button p-3"
      type="button"
      :disabled="processEvents.length === 0"
      @click="goToNewRequest(startEvent)"
    >
      <span class="px-3"> {{ $t('Start this process') }} </span>
    </button>
    <button
      v-else
      class="btn btn-success btn-lg dropdown-toggle start-button p-3"
      type="button"
      data-toggle="dropdown"
      aria-haspopup="true"
      aria-expanded="false"
      @click="getStartEvents()"
    >
      <span class="pl-3 pr-4"> {{ $t('Start this process') }} </span>
    </button>
    <div class="dropdown-menu scrollable-menu p-3 pb-0 mt-2">
      <p
        class="font-weight-bold px-1 text-uppercase"
        style="font-size: 14px"
      >
        {{ $t('Starting events') }}
      </p>
      <div
        v-for="event in processEvents"
        :key="event.id"
        class="mt-2"
      >
        <p
          class="text-capitalize mb-1"
          style="font-weight: 600"
        >
          {{ event.name }}
        </p>
        <button
          v-if="event.webEntry"
          type="button"
          class="btn btn-outline-primary border-0 p-1 text-capitalize"
          @click="copyLink(event.webEntry)"
        >
          <i class="fas fa-link p-1" />
          {{ $t('Copy Link') }}
        </button>
        <button
          v-else
          type="button"
          class="btn btn-outline-success border-0 p-1 text-capitalize"
          @click="goToNewRequest(event.id)"
        >
          <i class="fas fa-play-circle p-1" />
          {{ $t('Start') }}
        </button>
        <hr class="mt-2 mb-0">
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["process"],
  data() {
    return {
      processEvents: [],
      havelessOneStartEvent: false,
      startEvent: "",
    };
  },
  mounted() {
    this.getStartEvents();
  },
  methods: {
    /**
     * get start events for dropdown Menu
     */
    getStartEvents() {
      this.processEvents = [];
      ProcessMaker.apiClient
        .get(`process_bookmarks/processes/${this.process.id}/start_events`)
        .then((response) => {
          this.processEvents = response.data.data;
          if (this.processEvents.length <= 1) {
            const event = this.processEvents[0] ?? {};
            if (!("webEntry" in event)) {
              this.havelessOneStartEvent = true;
              this.startEvent = event.id ?? 0;
            }
          }
        })
        .catch(err => {
          this.disableButton();
          ProcessMaker.alert(err, "danger");
        });
    },
    /** 
     * Disable Start Button
     */
    disableButton() {
      this.havelessOneStartEvent = true;
      this.processEvents = [];
      this.startEvent = 0;
    },
    /**
     * Start new request
     */
    goToNewRequest(event) {
      ProcessMaker.apiClient
        .post(`/process_events/${this.process.id}?event=${event}`)
        .then((response) => {
          this.spin = 0;
          const instance = response.data;
          this.$cookies.set("fromTriggerStartEvent", true, "1min");
          window.location = `/requests/${instance.id}`;
        }).catch((err) => {
          const { data } = err.response;
          if (data.message) {
            ProcessMaker.alert(data.message, "danger");
          }
        });
    },
    /**
     * Copy WebEntry Link
     */
    copyLink(webEntry) {
      const link = webEntry.webentryRouteConfig.entryUrl;
      navigator.clipboard.writeText(link);
      ProcessMaker.alert(this.$t("Link copied"), "success");
    },
  },
};
</script>

<style scoped>
.start-button {
  background: #4EA075;
  border: 0px;
  width: 294px;
  font-size: 16px;
  font-weight: 600;
}

.scrollable-menu {
    height: auto;
    max-height: 280px;
    overflow-x: hidden;
    width: 294px;
    border-radius: 4px;
}
</style>
