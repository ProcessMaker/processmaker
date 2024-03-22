<template>
    <div class="mt-3">
      <div class="card" v-for="event in emptyStartEvents" :key="event.id">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <span
                v-uni-id="event.id.toString()"
                data-test="new-request-modal-process-name"
                :data-test-by-key="`new-request-modal-process-name-${process.id}-${event.id.toString()}`">
                  {{transformedName}}
              </span>
              <span v-if="process.startEvents.length > 1">: {{ event.name }}</span>
              <a href="#"
                @click="showRequestDetails"
                :aria-expanded="ariaExpanded"
                :aria-controls="getComputedId(process)"
                data-test="new-request-modal-show-process-details-button"
                :data-test-by-key="`new-request-modal-show-process-details-button-${process.id}-${event.id.toString()}`">
                ...
              </a>
            </div>
            <div class="text-right">
              <button
              :href="getNewRequestLinkHref(process, event)"
              @click.prevent="newRequestLink(process, event);"
              class="btn btn-primary btn-sm"
              v-uni-aria-describedby="event.id.toString()"
              data-test="new-request-modal-process-start-button"
              :data-test-by-key="`new-request-modal-process-start-button-${process.id}-${event.id.toString()}`"
              >
                <i class="fas fa-caret-square-right mr-1"></i> {{ $t('Start') }}
            </button>
            </div>
          </div>
          <div v-if="showdetail" :aria-hidden="ariaHidden" :id="getComputedId(process)">
            <hr>
            <p
              class="card-text text-muted"
              data-test="new-request-modal-process-description"
              :data-test-by-key="`new-request-modal-process-description-${process.id}-${event.id.toString()}`"
            >
              {{ process.description }}
            </p>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import { TooltipPlugin } from "bootstrap-vue";
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();
Vue.use(TooltipPlugin);

export default {
  mixins:[uniqIdsMixin],
  props: ["name", "description", "filter", "id", "process"],
  data() {
    return {
      disabled: false,
      spin: 0,
      showtip: true,
      showdetail: false,
    };
  },
  methods: {
    newRequestLink(process, event) {
      if (this.disabled) return;
      this.disabled = true;

      // Start a process
      this.spin = process.id + "." + event.id;
      let startEventId = event.id;

      window.ProcessMaker.apiClient
        .post("/process_events/" + this.process.id + "?event=" + startEventId)
        .then(response => {
          this.spin = 0;
          let instance = response.data;
          let url = '/requests';
          if (this.$cookies.get("isMobile") === "true") {
            url = '/requests/mobile';
          }
          window.location = `${url}/${instance.id}?fromTriggerStartEvent=`;
        }).catch((err) => {
          this.disabled = false;
          const data = err.response.data;
          if (data.message) {
            ProcessMaker.alert(data.message, 'danger');
          }
        });
    },
    showRequestDetails: function(id) {
      if (this.showdetail === false) {
        this.showdetail = true;
      } else {
        this.showdetail = false;
      }
    },
    getNewRequestLinkHref(process, event) {
      const id = process.id;
      const startEventId = event.id;
      return "/process_events/" + id + "?event=" + startEventId;
    },
    getComputedId(process) {
      return `process-${process.id}`
    },
  },
  computed: {
    ariaHidden() {
      return this.showdetail ? 'false' : 'true'
    },
    ariaExpanded() {
      return this.showdetail ? 'true' : 'false'
    },
    emptyStartEvents () {
      return this.process.startEvents.filter(event => !event.eventDefinitions || event.eventDefinitions.length === 0);
    },
    transformedName() {
      return this.process.name.replace(new RegExp(this.filter, "gi"), match => {
        return match;
      });
    },
    truncatedDescription() {
      if (!this.process.description) {
        return '<span class="text-primary"></span>';
      }

      let result = "";
      let container = this.$refs.description;
      let wordArray = this.process.description.split(" ");

      // Number of maximum characters we want for our description
      let maxLength = 100;
      let word = null;

      while ((word = wordArray.shift())) {
        if (result.length + word.length + 1 <= maxLength) {
          result = result + " " + word;
        }
      }

      return result.replace(new RegExp(this.filter, "gi"), match => {
        return '<span class="text-primary">' + match + "</span>";
      });
    }
  }
};
</script>
