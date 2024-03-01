<template>
  <div>
    <PMTable :headers="headers"
             :data="response"
             :partialURLString="partialURLString"
             :empty="$t('No results have been found')"
             :empty-desc="$t('We apologize, but we were unable to find any results that match your search. Please consider trying a different search. Thank you')"
             empty-icon="noData"
             @onRowMouseover="onRowMouseover"
             @onTrMouseleave="onTrMouseleave">

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

      <template v-slot:cell-end_date="{ row, header, rowIndex }">
        <PmRowButtons :ref="'pmRowButtons-'+rowIndex"
                      :value="row['end_date']"
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
        partialURLString: "",
        headers: this.columns(),
        response: {
          data: [],
          meta: {}
        },
        page: 1
      };
    },
    mounted() {
      this.getData();
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
            field: "active",
            width: 10
          },
          {
            label: this.$t("Creation Date"),
            field: "created_at",
            width: 10
          },
          {
            label: this.$t("Deactivation Date"),
            field: "end_date",
            width: 10
          }
        ];
      },
      getData() {
        let url = "tasks/rules";
        this.partialURLString = url;
        let params = {
          order: "desc",
          per_page: 10
        };
        ProcessMaker.apiClient
                .get(url, params)
                .then((response) => {
                  this.response = response.data;
                })
                .catch((error) => {
                });
      },
      onRowMouseover(row, scrolledWidth, index) {
        this.$refs["pmRowButtons-" + index].show();
        this.$refs["pmRowButtons-" + index].setMargin(scrolledWidth);
      },
      onTrMouseleave(row, index) {
        this.$refs["pmRowButtons-" + index].close();
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