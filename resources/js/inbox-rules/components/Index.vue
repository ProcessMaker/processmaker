<template>
  <div>
    <h4>{{$t('Inbox Rules')}}</h4>
    <b-tabs class="m-3" content-class="p-3 pm-tab-content">
      <b-tab :title="$t('Rules')"
             active>
        <PMTable :headers="headers"
                 :data="data"
                 @onRowMouseover="onRowMouseover"
                 @onTrMouseleave="onTrMouseleave"
                 :empty="$t('No results have been found')"
                 :empty-desc="$t('We apologize, but we were unable to find any results that match your search. Please consider trying a different search. Thank you')"
                 empty-icon="noData"
                 >

          <template v-slot:top-content>
            <PMSearchBar>
              <template v-slot:right-content>
                <b-button class="ml-md-1 d-flex align-items-center text-nowrap"
                          variant="primary"
                          @click="onCreateRule">
                  <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.5 6.5H10V2C10 1.44781 9.55219 1 9 1H8C7.44781 1 7 1.44781 7 2V6.5H2.5C1.94781 6.5 1.5 6.94781 1.5 7.5V8.5C1.5 9.05219 1.94781 9.5 2.5 9.5H7V14C7 14.5522 7.44781 15 8 15H9C9.55219 15 10 14.5522 10 14V9.5H14.5C15.0522 9.5 15.5 9.05219 15.5 8.5V7.5C15.5 6.94781 15.0522 6.5 14.5 6.5Z" fill="white"/>
                  </svg>
                  {{ $t('Create Rule') }}
                </b-button>
              </template>
            </PMSearchBar>
          </template>

          <template v-slot:cell-deactivation_date="{ row, header, rowIndex }">
            <PmRowButtons :ref="`pmRowButtons-${rowIndex}`"
                          :value="row['deactivation_date']"
                          :row="row"
                          @onButtonEdit="onButtonEdit"
                          @onButtonRemove="onButtonRemove">
            </PmRowButtons>
          </template>

        </PMTable>
      </b-tab>
      <b-tab :title="$t('Execution Log')" @click="$refs.executionLog.load()">
        <ExecutionLog ref="executionLog"></ExecutionLog>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
  import PMTable from "../../components/PMTable.vue";
  import PMSearchBar from "../../components/PMSearchBar.vue";
  import PmRowButtons from "./PmRowButtons.vue";
  import ExecutionLog from "./ExecutionLog.vue";

  export default {
    components: {
      PMTable,
      PMSearchBar,
      PmRowButtons,
      ExecutionLog,
    },
    data() {
      return {
        headers: this.getHeaders(),
        data: this.getData(),
        headers2: this.getHeaders2(),
        data2: this.getData2()
      };
    },
    mounted() {
    },
    methods: {
      onCreateRule() {
        this.$router.push({name: 'edit', params: {id: 1}});
      },
      getHeaders() {
        return [
          {
            label: this.$t("Name"),
            field: "name",
            width: 10
          },
          {
            label: this.$t("Status"),
            field: "status",
            width: 10
          },
          {
            label: this.$t("Creation Date"),
            field: "creation_date",
            width: 10
          },
          {
            label: this.$t("Deactivation Date"),
            field: "deactivation_date",
            width: 10
          }
        ];
      },
      getData() {
        let rows = [
          {
            id: "1",
            name: "name1",
            status: "ok",
            creation_date: "2024-01-01",
            deactivation_date: "2024-01-02"
          },
          {
            id: "2",
            name: "name2",
            status: "none",
            creation_date: "2024-02-01",
            deactivation_date: "2024-02-02"
          }
        ];
        let data = {
          data: rows,
          meta: {}
        };
        return data;
      },
      getHeaders2() {
        return [
          {
            label: this.$t("Case #"),
            field: "",
            width: 5
          },
          {
            label: this.$t("Case Name"),
            field: "",
            width: 8
          },
          {
            label: this.$t("Run Date"),
            field: "",
            width: 8
          },
          {
            label: this.$t("Applied Rule"),
            field: "",
            width: 15
          },
          {
            label: this.$t("Task due Date"),
            field: "",
            width: 15
          },
          {
            label: this.$t(""),
            field: "",
            width: 5
          },
          {
            label: this.$t("Task Name"),
            field: "",
            width: 10
          },
          {
            label: this.$t("Process Name"),
            field: "",
            width: 10
          },
          {
            label: this.$t("Status"),
            field: "",
            width: 8
          }
        ];
      },
      getData2() {
        let rows = [
          {
            name: "name1",
            status: "ok",
            creation_date: "2024-01-01",
            deactivation_date: "2024-01-02"
          },
          {
            name: "name2",
            status: "none",
            creation_date: "2024-02-01",
            deactivation_date: "2024-02-02"
          }
        ];
        let data = {
          data: rows,
          meta: {}
        };
        return data;
      },
      onRowMouseover(row, scrolledWidth, index) {
        this.$refs[`pmRowButtons-${index}`].show();
        this.$refs[`pmRowButtons-${index}`].setMargin(scrolledWidth);
      },
      onTrMouseleave(row, index) {
        this.$refs[`pmRowButtons-${index}`].close();
      },
      onButtonEdit(row) {
        this.$router.push({name: 'edit', params: {id: row.id}});
      },
      onButtonRemove(row) {
      }
    }
  }
</script>

<style>
  .pm-tab-content {
    border-top: 0px;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    border-left: 1px solid #dee2e6;
    background-color: white;
  }
</style>
<style scoped>
</style>