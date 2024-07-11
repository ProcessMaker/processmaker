<template>
  <div id="start-events btn-group mx-2">
    <button
      v-if="startEvent"
      class="btn btn-success start-button"
      type="button"
      :disabled="processEvents.length === 0"
      @click="goToNewRequest(startEvent)"
    >
      <i
        class="fa fa-play-circle"
        style="font-size: 16px;"
      />
      <span class="pl-2"> {{ displayTitle }} </span>
    </button>
    <button
      v-else
      class="btn btn-success btn-lg dropdown-toggle start-button justify-content-between"
      type="button"
      data-toggle="dropdown"
      aria-haspopup="true"
      aria-expanded="false"
    >
      <span>
        <i class="fa fa-play-circle" />
        <span class="pl-2"> {{ displayTitle }} </span>
      </span>
    </button>
    <div class="dropdown-menu dropdown-menu-right scrollable-menu p-3 pb-0 mt-2">
      <div
        v-for="event in processEvents"
        :key="event.id"
        class="dropdown-item start-event"
        type="button"
      >
        <p
          class="start-event-title"
        >
          {{ event.name }}
        </p>
        <button
          v-if="event.webEntry"
          type="button"
          class="btn button-start-event"
          @click="copyLink(event)"
        >
          <i class="fas fa-link pr-1" />
          {{ $t('Copy Link') }}
        </button>
        <button
          v-else
          type="button"
          class="btn button-start-event"
          @click="goToNewRequest(event.id)"
        >
          <i class="fas fa-play-circle pr-1" />
          {{ $t('Start Here') }}
        </button>
        <hr class="line-item">
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    process: {
      type: Object,
      required: true,
    },
    title: {
      type: String,
      default: null,
    },
    startEvent: {
      type: String,
      default: null,
    },
    processEvents: {
      type: Array,
      default: () => [],
    }
  },
  data() {
    return {
      anonUserId: "2",
    };
  },
  mounted() {
  },
  computed: {
    displayTitle() {
      if (this.title) {
        return this.title;
      }
      return this.$t("Start this process");
    },
  },
  methods: {
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
          window.location = `/requests/${instance.id}?fromTriggerStartEvent=`;
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
    copyLink(event) {
      const link = event.webEntry.webentryRouteConfig.entryUrl;
      navigator.clipboard.writeText(link);
      if (event.assignedUsers && event.assignedUsers === this.anonUserId) {
        const msg = this.$t("Please use this link when you are not logged into ProcessMaker");
        ProcessMaker.alert(msg, "success", 5, false, false, "", "Anonymous Web Link Copied");
      } else {
        ProcessMaker.alert(this.$t("Link copied"), "success");
      }
    },
  },
};
</script>

<style scoped lang="scss">
@import '~styles/variables';
.start-button {
  display: flex;
  align-items: center;
  background: #4EA075;
  border: 0px;
  border-radius: 8px;
  height: 40px;
  width: 249px;
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  line-height: 21px;
  letter-spacing: -0.02em;
  text-align: left;
  text-transform: capitalize;
  padding: 16px;

  i {
    font-size: 1.3em;
    vertical-align: top;
  }

  @media (max-width: $lp-breakpoint) {
    width: 100%;
    font-size: 18px;
  }
}
.scrollable-menu {
  overflow-x: auto;
}
.dropdown-menu.show {
  width: 316px;
  border-radius: 8px;
  border: 1px solid #cdddee;
  box-shadow: 0px 10px 20px 4px #00000021;
}
.dropdown-toggle::after {
  display: none;
}
.start-event {
  padding: 16px;
  padding-bottom: 0px;
}
.start-event-title {
  color: #566877;
  margin-bottom: 8px;
  text-transform: uppercase;
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  line-height: 21px;
  letter-spacing: -0.02em;
  text-align: left;
}
.line-item {
  margin-top: 16px;
  margin-bottom: 0px;
}
.button-start-event {
  color: #4ea075;
  text-transform: capitalize;
  padding: 4px 6px;
  border: 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: left;
}
.button-start-event:hover {
  color: white;
  background-color: #4ea075;
}
</style>
