<template>
  <div>
    <h3>Inbox Rules1</h3>
    Index Rules
    <router-link :to="{ name: 'edit', params: { id: 1 }}">
      Edit 1
    </router-link>

    <b-tabs class="m-3" content-class="p-3 pm-tab-content">
      <b-tab :title="$t('Rules')" 
             active>
        <FilterTable :headers="columns"
                     :data="rows"
                     @table-row-mouseover="tableRowMouseover"
                     @table-tr-mouseleave="tableTrMouseleave">
        </FilterTable>
      </b-tab>
      <b-tab :title="$t('Execution Log')">
        Hi!
      </b-tab>
    </b-tabs>


  </div>
</template>

<script>
  import { FilterTable } from "../../components/shared";
  import OptionsRow from "./OptionsRow.vue";
  Vue.component("OptionsRow", OptionsRow);
  export default {
    components: {
      FilterTable
    },
    mounted() {
    },
    data() {
      return {
        columns: this.getColumns(),
        rows: this.getRows()
      };
    },
    methods: {
      getColumns() {
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
      getRows() {
        let data = {
          data: [
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
          ]
        };
        for (let row of data.data) {
          row["deactivation_date"] = {
            component: "OptionsRow",
            props: {
              value: row["deactivation_date"],
              name: "deactivation_date",
              buttonEdit: () => {
                console.log("edit");
              },
              buttonRemove: () => {
                console.log("remove");
              },
              row: row
            }
          };
        }
        return data;
      },
      tableRowMouseover(row) {
        row["deactivation_date"].target.show();
      },
      tableTrMouseleave(row) {
        row["deactivation_date"].target.close();
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