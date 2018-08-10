<template>
    <div>
        <button id="navbar-request-button" class="btn btn-success btn-sm" @click="showRequestModal"><i class="fas fa-plus"></i> Request</button>
        <b-modal size="lg" id="requests-modal" class="requests-modal" ref="requestModalAdd" title="New Request" hide-footer>
            <div class="header">
                <div class="title">
                We've made it easy for you to make the following requests
                </div>
                <div class="search">
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><i class="fas fa-search"></i></div>
                        </div>
                        <input class="form-control form-control-sm" v-model="filter" placeholder="Search...">
                      </div>
                </div>
            </div>
            <div v-if="Object.keys(processes).length && !loading" class="process-list">
                <div class="category" v-for="(processList, index) in processes" :key="index">
                    <h3 class="name">{{index}}</h3>
                    <process-card v-for="(process,index) in processList" :filter="filter" :key="index" :process="process"></process-card>
                </div>
            </div>
            <div class="no-requests" v-if="!Object.keys(processes).length && !loading">
                No Requests have been defined yet or are available to you
            </div>
            <div v-if="loading" class="loading">
                Finding Requests available to you
            </div>
        </b-modal>
    </div>
</template>

<script>
import card from "./card";
import _ from "lodash";

export default {
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
    filter: _.debounce(function() {
      if (!this.loading) {
        this.fetch();
      }
    }, 250)
  },
  methods: {
    showRequestModal() {
      if (!this.loaded) {
        // Perform initial load of requests from backend
        this.$refs.requestModalAdd.show();
        this.fetch();
      }
    },
    fetch() {
      this.loading = true;
      // Now call our api
      // Maximum number of requests returned is 200 but should be enough
      // @todo Determine if we need to paginate or lazy scroll if someone has more than 200 requests
      window.ProcessMaker.apiClient
        .get("user/processes?filter=" + this.filter)
        .then(response => {
          let data = response.data;
          // Empty processes
          this.processes = {};
          // Now populate our processes array with data for rendering
          this.populate(data.data);
          // Do initial filter
          this.loading = false;
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
        let category = process.category ? process.category : "Uncategorized";
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
  .header {
    min-height: 74px;
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;

    .title {
      flex-grow: 1;
      font-size: 16px;
      font-weight: normal;
      font-style: normal;
      font-stretch: normal;
      line-height: normal;
      letter-spacing: normal;
      color: #333333;
      vertical-align: middle;
      padding-left: 60px;
    }

    .search {
      padding: 0px 32px;
    }

    .actions {
      padding-right: 32px;
      font-size: 19px;
    }
  }

  .loading,
  .no-requests {
    padding: 32px 60px;
    font-size: 16px;
    font-weight: bold;
  }

  .process-list {
    //flex-grow: 1;
    overflow: auto;
    padding: 32px 60px;

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







 



  

        

        

        
                