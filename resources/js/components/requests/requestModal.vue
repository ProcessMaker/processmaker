<template>
  <div>
    <b-modal
      id="requests-modal"
      ref="requestModalAdd"
      class="requests-modal modal-dialog-scrollable"
      header-close-content="&times;"
      hide-footer
      :title="$t('New Case')"
      :size="size"
    >
      <progress-loader
        ref="progressLoader"
        v-if="startingRequest"
        :type="'radial'"
      />

      <b-row no-gutters>
        <b-col
          cols="12"
          md="8"
        >
          <p>
            {{ $t("Select a process below to get started.") }}
          </p>
        </b-col>
        <b-col
          cols="12"
          md="4"
        >
          <b-input-group class="search">
            <b-form-input
              data-test="new-request-modal-search-input"
              v-model="filter"
              :placeholder="$t('Search') + '...'"
            />
            <b-input-group-append>
              <button
                data-test="new-request-modal-search-button"
                type="button"
                class="btn btn-primary"
                :aria-label="$t('Search')"
              >
                <i class="fas fa-search" />
              </button>
            </b-input-group-append>
          </b-input-group>
        </b-col>
      </b-row>

      <div
        v-if="Object.keys(processes).length && !loading"
        class="process-list"
      >
        <div
          v-for="(category, index) in processes"
          :key="`category-${index}`"
          class="mt-3"
        >
          <h5 class="mb-n2">
            <span data-test="new-request-modal-category-name" :data-test-by-key="`new-request-modal-category-name-${index}`">
              {{ index }}
            </span>
            <span
              class="badge badge-pill badge-secondary"
              data-test="new-request-modal-category-count"
              :data-test-by-key="`new-request-modal-category-count-${index}`"
            >
              {{ category.length }}
            </span>
          </h5>
          <template v-for="(process, id) in category">
            <process-card
              v-if="hasEmptyStartEvents(process)"
              :key="`process-${id}`"
              :filter="filter"
              :process="process"
            />
          </template>
        </div>
      </div>

      <div
        v-if="!Object.keys(processes).length && !loading"
        class="no-requests my-3 text-center"
      >
        <h4>{{ $t('You don\'t have any Processes.') }}</h4>
        <span v-if="permission.includes('create-processes')">
          <a
            :href="url"
            class="text-primary"
          >
            {{ $t('Please visit the Processes page') }}
          </a>
          {{ $t('and click on +Process to get started.') }}
        </span>
        <span v-else>
          {{ $t('Please contact your administrator to get started.') }}
        </span>
      </div>

      <div
        v-if="loading"
        class="loading no-requests my-3 text-center"
      >
        <img
          class="m-3"
          src="/img/new-case-load.svg"
          alt="new-case-loading"
          width="209"
          height="155"
        >
        <p class="loading-text loading-title">
          {{ $t('Finding your processes') }}
        </p>
        <p class="loading-text">
          {{ $t('Please wait a moment while we locate and load your processes.') }}
        </p>
        <p class="loading-text">
          {{ $t('This should only take a few seconds.') }}
        </p>
      </div>

      <pagination
        ref="listProcess"
        :single="$t('Process')"
        :plural="$t('Processes')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        data-test="new-request-modal-pagination"
      />
    </b-modal>
  </div>
</template>

<script>
import card from "./card.vue";
import datatableMixin from "../common/mixins/datatable";
import progressLoader from "../common/ProgressLoader.vue";

export default {
  components: {
    "process-card": card,
    "progress-loader": progressLoader,
  },
  mixins: [datatableMixin],
  props: {
    permission: Array,
    url: "",
    size: "",
  },
  data() {
    return {
      filter: "",
      loading: false,
      error: false,
      loaded: false,
      processes: {},
      startingRequest: false,
    };
  },
  mounted() {
    ProcessMaker.EventBus.$on("start-request", () => {
      this.startingRequest = true;
    });
  },
  methods: {
    hasEmptyStartEvents(process) {
      return !!process.events.find((event) => !event.eventDefinitions || event.eventDefinitions.length === 0);
    },
    showModal() {
      this.loaded = true;
      // Perform initial load of requests from backend
      this.$refs.requestModalAdd.show();
      this.fetch();
    },
    // Overwrite handler to change the page based on events fired
    onPageChange: function onPageChange(page) {
      if (page === "next") {
        this.page += 1;
      } else if (page === "prev") {
        this.page -= 1;
      } else {
        this.page = page;
      }
      if (this.page <= 0) {
        this.page = 1;
      }
      if (this.page > this.$refs.listProcess.tablePagination.last_page) {
        this.page = this.$refs.listProcess.tablePagination.last_page;
      }
      this.fetch();
    },
    fetch() {
      if (!this.loaded) {
        return;
      }
      this.loading = true;
      // Now call our api
      window.ProcessMaker.apiClient
        .get(
          `start_processes?page=${
            this.page
          }&per_page=${
            this.perPage
          }&filter=${
            this.filter
          }&order_by=category.name,name`
            + "&order_direction=asc,asc"
            + "&include=events,categories"
            + "&without_event_definitions=true",
        )
        .then((response) => {
          const { data } = response;
          // Empty processes
          this.processes = {};
          // Now populate our processes array with data for rendering
          this.populate(data.data);
          // Do initial filter
          this.loading = false;
          // Set data in paginate
          data.meta.from -= 1;
          this.$refs.listProcess.data = data;
          this.$refs.listProcess.setPaginationData(data.meta);
        })
        .catch(() => {
          this.loading = false;
          this.error = true;
        });
    },
    populate(data) {
      // Each element in data represents an individual process
      // We need to pull out the category name, and if it's available in our processes, append it there
      // if not, create the category in our processes array and then append it
      for (const process of data) {
        for (const category of process.categories) {
          // Now determine if we have it defined in our processes list
          if (typeof this.processes[category.name] === "undefined") {
            // Create it
            this.processes[category.name] = [];
          }
          // Now append
          this.processes[category.name].push(process);
        }
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-container {
  display:inline-block;
  width: 5em;
  margin-bottom: 1em;

  i {
    color: #b7bfc5;
    font-size: 5em;
  }

  svg {
    fill: #b7bfc5;
  }
}
.search {
  width: 100%;
}
.loading-text {
  color: #556271;
  margin: 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 27px;
  letter-spacing: -0.02em;
}
.loading-title {
  margin-bottom: 8px;
  font-family: 'Open Sans', sans-serif;
  font-size: 24px;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -0.04em;
}
.process-list {
  height: calc(100vh - 14rem);
  overflow-y: auto;
}
</style>
