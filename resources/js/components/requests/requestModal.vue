<template>
  <b-modal
    id="requests-modal"
    ref="requestModalAdd"
    class="requests-modal modal-dialog-scrollable"
    header-close-content="&times;"
    hide-footer
    :title="$t('New Case')"
    :size="size"
  >
    <b-row no-gutters>
      <b-col
        cols="12"
        md="8"
      >
        <p>
          {{ $t("Select a Process below to get started.") }}
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
      <div class="icon-container">
        <div>
          <svg
            class="lds-gear"
            width="100%"
            height="100%"
            xmlns="http://www.w3.org/2000/svg"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            viewBox="0 0 100 100"
            preserveAspectRatio="xMidYMid"
          >
            <g transform="translate(50 50)">
              <g transform="rotate(248.825)">
                <animateTransform
                  attributeName="transform"
                  type="rotate"
                  values="0;360"
                  keyTimes="0;1"
                  dur="4.7s"
                  repeatCount="indefinite"
                />
                <path d="M37.43995192304605 -6.5 L47.43995192304605 -6.5 L47.43995192304605 6.5 L37.43995192304605 6.5 A38 38 0 0 1 35.67394948182593 13.090810836924174 L35.67394948182593 13.090810836924174 L44.33420351967032 18.090810836924174 L37.83420351967032 29.34914108612188 L29.17394948182593 24.34914108612188 A38 38 0 0 1 24.34914108612188 29.17394948182593 L24.34914108612188 29.17394948182593 L29.34914108612188 37.83420351967032 L18.090810836924184 44.33420351967032 L13.090810836924183 35.67394948182593 A38 38 0 0 1 6.5 37.43995192304605 L6.5 37.43995192304605 L6.500000000000001 47.43995192304605 L-6.499999999999995 47.43995192304606 L-6.499999999999996 37.43995192304606 A38 38 0 0 1 -13.09081083692417 35.67394948182593 L-13.09081083692417 35.67394948182593 L-18.09081083692417 44.33420351967032 L-29.34914108612187 37.834203519670325 L-24.349141086121872 29.173949481825936 A38 38 0 0 1 -29.17394948182592 24.34914108612189 L-29.17394948182592 24.34914108612189 L-37.83420351967031 29.349141086121893 L-44.33420351967031 18.0908108369242 L-35.67394948182592 13.090810836924193 A38 38 0 0 1 -37.43995192304605 6.5000000000000036 L-37.43995192304605 6.5000000000000036 L-47.43995192304605 6.500000000000004 L-47.43995192304606 -6.499999999999993 L-37.43995192304606 -6.499999999999994 A38 38 0 0 1 -35.67394948182593 -13.090810836924167 L-35.67394948182593 -13.090810836924167 L-44.33420351967032 -18.090810836924163 L-37.834203519670325 -29.34914108612187 L-29.173949481825936 -24.34914108612187 A38 38 0 0 1 -24.349141086121893 -29.17394948182592 L-24.349141086121893 -29.17394948182592 L-29.349141086121897 -37.834203519670304 L-18.0908108369242 -44.334203519670304 L-13.090810836924195 -35.67394948182592 A38 38 0 0 1 -6.500000000000005 -37.43995192304605 L-6.500000000000005 -37.43995192304605 L-6.500000000000007 -47.43995192304605 L6.49999999999999 -47.43995192304606 L6.499999999999992 -37.43995192304606 A38 38 0 0 1 13.090810836924149 -35.67394948182594 L13.090810836924149 -35.67394948182594 L18.090810836924142 -44.33420351967033 L29.349141086121847 -37.83420351967034 L24.349141086121854 -29.17394948182595 A38 38 0 0 1 29.17394948182592 -24.349141086121893 L29.17394948182592 -24.349141086121893 L37.834203519670304 -29.349141086121897 L44.334203519670304 -18.0908108369242 L35.67394948182592 -13.090810836924197 A38 38 0 0 1 37.43995192304605 -6.500000000000007 M0 -20A20 20 0 1 0 0 20 A20 20 0 1 0 0 -20" />
              </g>
            </g>
          </svg>
        </div>
      </div>
      <h4>{{ $t('Finding Cases available to you...') }}</h4>
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
</template>

<script>
import card from "./card.vue";
import datatableMixin from "../common/mixins/datatable";

export default {
  components: {
    "process-card": card,
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
    };
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
</style>
