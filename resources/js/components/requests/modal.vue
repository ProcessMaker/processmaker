<template>
    <div v-cloak>
        <button type="button" id="navbar-request-button" class="btn btn-success btn-sm" @click="showRequestModal">
            <i class="fas fa-plus"></i>
            {{$t('Request')}}
        </button>
        <b-modal size="lg"
                 id="requests-modal"
                 class="requests-modal modal-dialog-scrollable"
                 ref="requestModalAdd"
                 :title="$t('New Request')"
                 hide-footer>
            <b-row no-gutters>
                <b-col cols="8">
                    <p>{{ $t("We've made it easy for you to start a Request for the following Processes. Select a Process to start your Request.") }}</p>
                </b-col>
                <b-col cols="4">
                    <b-input-group>
                        <b-input-group-text slot="prepend">
                            <i class="fas fa-search"></i>
                        </b-input-group-text>
                        <b-form-input v-model="filter" :placeholder="$t('Search') + '...'"></b-form-input>
                    </b-input-group>
                </b-col>
            </b-row>

            <div v-if="Object.keys(processes).length && !loading" class="process-list p-2">
                <div v-for="(category, index) in processes">
                    <h5>
                        {{index}}
                        <span class="badge badge-pill badge-secondary">{{category.length}}</span>
                    </h5>
                    <process-card v-for="(process,index) in category"
                                  :filter="filter"
                                  :key="index"
                                  :process="process">
                    </process-card>
                </div>
            </div>

            <div class="no-requests" v-if="!Object.keys(processes).length && !loading">
                <h4>{{ $t('You don\'t have any Processes.') }}</h4>
                <span v-if="permission.includes('create-processes')">
                    <span @click="redirect" class="text-primary">{{ $t('Please visit the Processes page') }}
                    </span>
                    {{ $t('and click on +Process to get started.') }}
                </span>
                <span v-else>{{ $t('Please contact your administrator to get started.') }}</span>
            </div>

            <div v-if="loading" class="loading">{{ $t('Finding Requests available to you...') }}</div>

            <pagination :single="$t('Process')"
                        :plural="$t('Processes')"
                        :perPageSelectEnabled="true"
                        @changePerPage="changePerPage"
                        @vuetable-pagination:change-page="onPageChange"
                        ref="listProcess">
            </pagination>

        </b-modal>
    </div>
</template>

<script>
  import card from "./card";
  import datatableMixin from "../common/mixins/datatable";

  export default {
    mixins: [datatableMixin],
    props: {
      permission: Array
    },
    components: {
      "process-card": card
    },
    data() {
      return {
        filter: "",
        loading: false,
        error: false,
        loaded: false,
        processes: {
          // Blank
        }
      };
    },
    methods: {
      redirect() {
        window.location = "/processes";
      },
      showRequestModal() {
        this.loaded = true;
        // Perform initial load of requests from backend
        this.$refs.requestModalAdd.show();
        this.fetch();
      },
      // Overwrite handler to change the page based on events fired
      onPageChange: function onPageChange(page) {
        if (page === "next") {
          this.page++;
        } else if (page === "prev") {
          this.page--;
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
            "start_processes?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=category.name,name" +
            "&order_direction=asc,asc" +
            "&include=events,category"
          )
          .then(response => {
            let data = response.data;
            // Empty processes
            this.processes = {};
            // Now populate our processes array with data for rendering
            this.populate(data.data);
            // Do initial filter
            this.loading = false;
            //Set data in paginate
            data.meta.from--;
            this.$refs.listProcess.data = data;
            this.$refs.listProcess.setPaginationData(data.meta);
          })
          .catch(error => {
            this.loading = false;
            this.error = true;
          });
      },
      populate(data) {
        // Each element in data represents an individual process
        // We need to pull out the category name, and if it's available in our processes, append it there
        // if not, create the category in our processes array and then append it
        for (let process of data) {
          let category = process.category
            ? process.category.name
            : "Uncategorized";
          // Now determine if we have it defined in our processes list
          if (typeof this.processes[category] == "undefined") {
            // Create it
            this.processes[category] = [];
          }
          // Now append
          this.processes[category].push(process);
        }
      }
    }
  };
</script>

<style lang="scss" scoped>
</style>
