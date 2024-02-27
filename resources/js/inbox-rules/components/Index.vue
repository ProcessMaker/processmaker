<template>
  <div>
    <h4>Inbox Rules</h4>
    <b-tabs class="m-3" content-class="p-3 pm-tab-content">
      <b-tab :title="$t('Rules')" 
             active>
        <PMTable :headers="headers"
                 :data="data"
                 @onRowMouseover="tableRowMouseover"
                 @onTrMouseleave="tableTrMouseleave">

          <template v-slot:cell-deactivation_date="{ row, header, rowIndex }">
            <PmRowButtons
              :row="row"
              name="deactivation_date"
              :value="row['deactivation_date']"
              @buttonEdit="$router.push({name: 'edit', params: {id: row.id}})"
              @buttonRemove="console.log('remove')"
              :ref="`pmRowButtons-${rowIndex}`"
            >
            </PmRowButtons>
          </template>

        </PMTable>
      </b-tab>
      <b-tab :title="$t('Execution Log')">
        <PMTable :headers="headers2"
                 :data="data2"
                 @onRowMouseover="tableRowMouseover"
                 @onTrMouseleave="tableTrMouseleave">
        </PMTable>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
  import PMTable from "../../components/PMTable.vue";
  import PmRowButtons from "./PmRowButtons.vue";
  export default {
    components: {
      PMTable,
      PmRowButtons,
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
      getHeaders2() {
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
      tableRowMouseover(row, scrolledWidth, index) {
        this.$refs[`pmRowButtons-${index}`].show();
        this.$refs[`pmRowButtons-${index}`].setMargin(scrolledWidth);
      },
      tableTrMouseleave(row, index) {
        this.$refs[`pmRowButtons-${index}`].close();
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