<template>
    <div>
        <button id="navbar-request-button" class="btn btn-success btn-sm" @click="showRequestModal">
            <i class="fas fa-plus"></i>
            {{__('Request')}}
        </button>
        <b-modal size="lg"
                 id="requests-modal"
                 class="requests-modal modal-dialog-scrollable"
                 ref="requestModalAdd"
                 :title="__('New Request')"
                 hide-footer>
            <span class="float-right">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    <input class="form-control form-control-sm" v-model="filter" :placeholder="__('Search...')">
                </div>
            </span>

            <p>{{__('We\'ve made it easy for you to make the following Requests.')}}</p>
            <div v-if="Object.keys(processes).length && !loading" class="process-list p-4">
                <div class="category" v-for="(category, index) in processes">
                    <h3 class="name">{{index}}</h3>
                    <process-card v-for="(process,index) in category"
                                  :filter="filter"
                                  :key="index"
                                  :process="process">
                    </process-card>
                </div>
            </div>

            <div class="no-requests" v-if="!Object.keys(processes).length && !loading">
                <h4>{{__('You don\'t have any Processes.')}}</h4>
                <span v-if="permission.includes('create-processes')">
                    <a href="/processes">{{__('Please visit the Processes page')}}</a>
                    {{__('and click on +Process to get started.')}}
                </span>
                <span v-else>{{__('Please contact your administrator to get started.')}}</span>
            </div>
            <div v-if="loading" class="loading">{{__('Finding Requests available to you...')}}</div>

            <pagination single="Process"
                        plural="Processes"
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
  import _ from "lodash";
  import datatableMixin from "../common/mixins/datatable";
window.__ = __;
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
        processes: {
          // Blank
        }
      };
    },
    watch: {
      filter: _.debounce(function () {
        if (!this.loading) {
          this.fetch();
        }
      }, 250)
    },
    methods: {
      __(variable) {
        return __(variable);
      },
      showRequestModal() {
        if (!this.loaded) {
          // Perform initial load of requests from backend
          this.$refs.requestModalAdd.show();
          this.fetch();
        }
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
        this.loading = true;
        // Now call our api
        // Maximum number of requests returned is 200 but should be enough
        // @todo Determine if we need to paginate or lazy scroll if someone has more than 200 requests
        window.ProcessMaker.apiClient
          .get(
            "start_processes?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
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
    .requests-modal {
        .loading,
        .no-requests {
            padding: 32px 60px;
            font-size: 16px;
            font-weight: bold;
        }

        .process-list {
            //flex-grow: 1;
            overflow: auto;

            .category {
                padding-bottom: 32px;

                .name {
                    font-size: 16px;
                    font-weight: bold;
                    font-style: normal;
                    font-stretch: normal;
                    line-height: normal;
                    letter-spacing: normal;
                    color: #788793;
                }
            }

            .processes {
                display: flex;
                flex-flow: row wrap;
            }
        }

        &.show {
            display: flex;
            flex-direction: column;
        }
    }
</style>
