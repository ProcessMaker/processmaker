<template>
  <div id="start-events btn-group mx-2">
    <button
      v-if="startEvent"
      class="btn btn-success start-button"
      type="button"
      :disabled='isStartButtonDisabled'
      @click="goToNewRequest(startEvent)"
    >
      <i class="fa fa-play-circle" />
      <span class="pl-2"> {{ displayTitle }} </span>
    </button>
    <button
      v-else
      class="btn btn-success start-button justify-content-between"
      type="button"
      data-toggle="dropdown"
      aria-haspopup="true"
      aria-expanded="false"
      :disabled='isStartButtonDisabled'
    >
      <i class="fa fa-play-circle" />
      <span class="pl-2"> {{ displayTitle }} </span>
    </button>
    <div class="dropdown-menu dropdown-menu-right scrollable-menu p-3 pb-0 mt-2">
      <div
        v-for="event in processEvents"
        :key="event.id"
        class="dropdown-item dropdown-item-div"
        type="button"
      >
        <div v-if="event.webEntry" 
             class="start-event"
             @click="copyLink(event)"
             v-b-tooltip.hover.top.options="{ boundary: 'viewport' }" 
             :title=" sizeEventName(event.name) ? event.name : '' ">
          <button class="btn button-start-event">
            <i class="fas fa-link pr-1" />
          </button>
          {{ formatEventName(event.name) }}
          <div class="start-event-label">
            {{ $t('Copy link') }}  
          </div>
        </div>
        <div v-else
             class="start-event"
             @click="goToNewRequest(event.id)"
             v-b-tooltip.hover.top.options="{ boundary: 'viewport' }" 
             :title=" sizeEventName(event.name) ? event.name: '' ">
          <button class="btn button-start-event">
            <i class="fas fa-play-circle pr-1" />  
          </button>
          {{ formatEventName(event.name) }}
          <div class="start-event-label">
            {{ $t('Start') }}
          </div>
        </div>
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
  computed: {
    isStartButtonDisabled() {
      return this.processEvents.length === 0;
    },
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
    sizeEventName(string){
      return string.length > 25;
    },
    formatEventName(string) {
      if (this.sizeEventName(string)) {
        string = string.slice(0, 25) + "...";
      }
      return string;
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
  text-transform: capitalize;
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
  padding: initial !important;
}
.dropdown-toggle::after {
  display: none;
}
.dropdown-item-div {
  padding: initial;
}
.start-event {
  display: flex;
  padding: 10px;
  width: 100%;
}
.dropdown-item:hover {
  background-color: #E0F5E7;
}
.dropdown-item:hover .start-event-label{
  color: #4EA075;
}
.button-start-event {
  color: #4ea075;
  text-transform: capitalize;
  padding: initial;
}
.button-start-event:hover {
  color: white;
  background-color: #4ea075;
}
.start-event-label {
  margin-left: auto;
  font-style: italic;
  font-size: 14px;
  color: darkgray;
}
</style>
