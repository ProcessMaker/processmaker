<template>
    <div class="mt-3">
      <div class="card" v-for="event in emptyStartEvents" :key="event.id">
        <div class="card-body">
          <div class="row">
            <div class="col-10">
              {{transformedName}}
              <span v-if="process.startEvents.length > 1">: {{event.name}}</span>
              <a href="#" @click="showRequestDetails">...</a>
            </div>
            <div class="col-2 text-right">
              <a :href="getNewRequestLinkHref(process, event)" @click.prevent="newRequestLink(process, event);" class="btn btn-primary btn-sm">
                <i class="fas fa-caret-square-right"></i> {{$t('Start')}}
              </a>
            </div>
          </div>
          <div v-if="showdetail">
            <hr>
            <p class="card-text text-muted">{{process.description}}</p>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import { TooltipPlugin } from "bootstrap-vue";
Vue.use(TooltipPlugin);
export default {
  props: ["name", "description", "filter", "id", "process"],
  data() {
    return {
      disabled: false,
      spin: 0,
      showtip: true,
      showdetail: false
    };
  },
  methods: {
    newRequestLink(process, event) {
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      //Start a process
      this.spin = process.id + "." + event.id;
      let startEventId = event.id;
      window.ProcessMaker.apiClient
        .post("/process_events/" + this.process.id + "?event=" + startEventId)
        .then(response => {
          this.spin = 0;
          var instance = response.data;
          window.location = "/requests/" + instance.id;
        }).catch((err) => {
          this.disabled = false;
          const data = err.response.data;
          if (data.message) {
            ProcessMaker.alert(data.message, 'danger');
          }
        });
    },
    showRequestDetails: function(id) {
      if (this.showdetail == false) {
        this.showdetail = true;
      } else {
        this.showdetail = false;
      }
    },
    getNewRequestLinkHref(process, event) {
      const id = process.id;
      const startEventId = event.id;
      return "/process_events/" + id + "?event=" + startEventId;
    }
  },
  computed: {
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
          continue;
        } else {
          break;
        }
      }
      return result.replace(new RegExp(this.filter, "gi"), match => {
        return '<span class="text-primary">' + match + "</span>";
      });
    }
  }
};
</script>
