<template>
  <div>
    <PMTable :headers="headers"
             :data="response"
             @onRowMouseover="onRowMouseover"
             @onTrMouseleave="onTrMouseleave"
             :empty="$t('No results have been found')"
             :empty-desc="$t('We apologize, but we were unable to find any results that match your search. Please consider trying a different search. Thank you')"
             empty-icon="noData">

      <template v-slot:top-content>
        <PMSearchBar>
          <template v-slot:right-content>
            <b-button class="ml-md-1 d-flex align-items-center text-nowrap"
                      variant="primary"
                      @click="onCreateRule">
              <img src="/img/plus-lg.svg" :alt="$t('Create Rule')">
                {{ $t('Create Rule') }}
            </b-button>
          </template>
        </PMSearchBar>
      </template>

      <template v-slot:cell-deactivation_date="{ row, header, rowIndex }">
        <PmRowButtons :ref="`pmRowButtons-${rowIndex}`"
                      :value="row['deactivation_date']"
                      :row="row"
                      @onEditRule="onEditRule"
                      @onRemoveRule="onRemoveRule">
        </PmRowButtons>
      </template>

    </PMTable>
  </div>
</template>

<script>
  import PMTable from "../../components/PMTable.vue";
  import PMSearchBar from "../../components/PMSearchBar.vue";
  import PmRowButtons from "./PmRowButtons.vue";
  export default {
    components: {
      PMTable,
      PMSearchBar,
      PmRowButtons
    },
    data() {
      return {
        headers: this.columns(),
        response: {
          data: this.getData(),
          meta: {}
        },
        page: 1
      };
    },
    mounted() {
    },
    methods: {
      columns() {
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
        return rows;
      },
      onRowMouseover(row, scrolledWidth, index) {
        this.$refs[`pmRowButtons-${index}`].show();
        this.$refs[`pmRowButtons-${index}`].setMargin(scrolledWidth);
      },
      onTrMouseleave(row, index) {
        this.$refs[`pmRowButtons-${index}`].close();
      },
      onCreateRule() {
        this.$router.push({name: 'edit', params: {id: 1}});
      },
      onEditRule(row) {
        this.$router.push({name: 'edit', params: {id: row.id}});
      },
      onRemoveRule(row) {
      }
    }
  };
</script>