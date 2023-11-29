<template>
  <div id="start-events btn-group">
    <button
      class="btn btn-success btn-lg dropdown-toggle start-button p-3"
      type="button"
      data-toggle="dropdown"
      aria-haspopup="true"
      aria-expanded="false"
    >
      <span class="pl-3 pr-4"> {{ $t('Start this process') }} </span>
    </button>
    <div
      class="dropdown-menu scrollable-menu p-3 pb-0 mt-2"
      style="width: 248px; border-radius: 5px;"
    >
      <p
        class="font-weight-bold px-1"
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
          type="button"
          class="btn btn-outline-success border-0 p-1"
          @click="goToNewRequest(event.id)"
        >
          <i class="fas fa-play-circle p-1" />
          {{ $t('Start') }}
        </button>
        <hr class="mt-2 mb-0">
      </div>
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
          type="button"
          class="btn btn-outline-success border-0 p-1"
          @click="goToNewRequest(event.id)"
        >
          <i class="fas fa-play-circle p-1" />
          {{ $t('Start') }}
        </button>
        <hr class="mt-2 mb-0">
      </div>
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
          type="button"
          class="btn btn-outline-success border-0 p-1"
          @click="goToNewRequest(event.id)"
        >
          <i class="fas fa-play-circle p-1" />
          {{ $t('Start') }}
        </button>
        <hr class="mt-2 mb-0">
      </div>
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
          type="button"
          class="btn btn-outline-success border-0 p-1"
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
  props: ["processId"],
  data() {
    return {
      processEvents: [],
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
      window.ProcessMaker.apiClient
        .get(`processes/${this.processId}`)
        .then((response) => {
          const startEvents = response.data.start_events;
          startEvents.forEach((event) => {
            if (event.eventDefinitions.length === 0) {
              this.processEvents.push(event);
            }
          });
        });
    },
    /**
     * Start new request
     */
    goToNewRequest(event) {
      ProcessMaker.apiClient
        .post(`/process_events/${this.processId}?event=${event}`)
        .then((response) => {
          this.spin = 0;
          let instance = response.data;
          this.$cookies.set("fromTriggerStartEvent", true, "1min");
          window.location = `/requests/${instance.id}`;
        }).catch((err) => {
          const data = err.response.data;
          if (data.message) {
            ProcessMaker.alert(data.message, "danger");
          }
        });
    },
  },
};
</script>

<style scoped>
.start-button {
  background: #4EA075;
  border: 0px;
  font-size: 16px;
  font-weight: 600;
}

.scrollable-menu {
    height: auto;
    max-height: 280px;
    overflow-x: hidden;
}
</style>
