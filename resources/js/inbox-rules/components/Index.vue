<template>
  <div>
    <h4>Inbox Rules</h4>
    <b-tabs class="m-3" content-class="p-3 pm-tab-content">
      <b-tab :title="$t('Rules')" 
             active>
        <PMTable :headers="columns"
                 :data="rows"
                 @onRowMouseover="tableRowMouseover"
                 @onTrMouseleave="tableTrMouseleave">
        </PMTable>
      </b-tab>
      <b-tab :title="$t('Execution Log')">
        Hi!
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
  import PMTable from "../../components/PMTable.vue";
  import PmRowButtons from "./PmRowButtons.vue";
  Vue.component("PmRowButtons", PmRowButtons);
  export default {
    components: {
      PMTable
    },
    data() {
      return {
        columns: this.getHeaders(),
        rows: this.getData()
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
        this.formatColumns(data);
        return data;
      },
      formatColumns(data) {
        for (let row of data.data) {
          row["deactivation_date"] = this.formatToDeactivationDate(row);
        }
      },
      formatToDeactivationDate(row) {
        return {
          component: "PmRowButtons",
          props: {
            value: row["deactivation_date"],
            name: "deactivation_date",
            row: row,
            buttonEdit: () => {
              this.$router.push({name: 'edit', params: {id: 1}});
            },
            buttonRemove: () => {
              console.log("remove");
            }
          }
        };
      },
      tableRowMouseover(row, scrolledWidth) {
        row["deactivation_date"].pmRowButtons.show();
        row["deactivation_date"].pmRowButtons.setMargin(scrolledWidth);
      },
      tableTrMouseleave(row) {
        row["deactivation_date"].pmRowButtons.close();
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