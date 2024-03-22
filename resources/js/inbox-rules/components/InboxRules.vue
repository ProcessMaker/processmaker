<template>
  <div>
    <PMTable :headers="headers"
             :data="responseData"
             :baseURL="baseURL"
             :empty="$t('No results have been found')"
             :empty-desc="$t('We apologize, but we were unable to find any results that match your search. Please consider trying a different search. Thank you')"
             empty-icon="noData"
             @onRowMouseover="onRowMouseover"
             @onTrMouseleave="onTrMouseleave"
             @onPageChange="onPageChange">

      <template v-slot:top-content>
        <PMSearchBar v-model="filter">
          <template v-slot:right-content>
            <b-button class="ml-md-1 d-flex align-items-center text-nowrap"
                      variant="primary"
                      @click="onCreateRule"
                      data-cy="createRule">
              <img src="/img/plus-lg.svg" :alt="$t('Create Rule')"/>
              {{ $t('Create Rule') }}
            </b-button>
          </template>
        </PMSearchBar>
      </template>

      <template v-slot:cell-active="{ row, header, rowIndex }">
        <b-form-checkbox v-model="row['active']"
                         @change="onChangeStatus($event,row)"
                         switch
                         :data-cy="'statusSwitch'+rowIndex">
        </b-form-checkbox>
      </template>

      <template v-slot:cell-created_at="{ row, header, rowIndex }">
        {{ convertUTCToPMFormat(row['created_at']) }}
      </template>

      <template v-slot:cell-end_date="{ row, header, rowIndex }">
        <InboxRulesRowButtons :ref="'inboxRulesRowButtons-'+rowIndex"
                              :value="convertUTCToPMFormat(row['end_date'])"
                              :row="row"
                              @onEditRule="onEditRule"
                              @onRemoveRule="onRemoveRule"
                              :data-cy="'inboxRulesRowButtons'+rowIndex">
        </InboxRulesRowButtons>
      </template>

    </PMTable>
  </div>
</template>

<script>
  import PMTable from "../../components/PMTable.vue";
  import PMSearchBar from "../../components/PMSearchBar.vue";
  import InboxRulesRowButtons from "./InboxRulesRowButtons.vue";
  export default {
    components: {
      PMTable,
      PMSearchBar,
      InboxRulesRowButtons
    },
    data() {
      return {
        responseData: {data: [], meta: {}},
        headers: this.columns(),
        baseURL: "tasks/rules",
        page: 1,
        per_page: 10,
        order_by: "id",
        order_direction: "asc",
        filter: ""
      };
    },
    mounted() {
      this.requestData();
    },
    watch: {
      page() {
        this.requestData();
      },
      filter() {
        this.requestData();
      }
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
      requestData() {
        let url = this.baseURL + "?"
                + "page=" + this.page + "&"
                + "per_page=" + this.per_page + "&"
                + "order_by=" + this.order_by + "&"
                + "order_direction=" + this.order_direction + "&"
                + "filter=" + this.filter;

        ProcessMaker.apiClient.get(url)
                .then((response) => {
                  this.responseData = response.data;
                })
                .catch((error) => {
                });
      },
      onPageChange(page) {
        this.page = page;
      },
      onRowMouseover(row, scrolledWidth, index) {
        this.$refs["inboxRulesRowButtons-" + index].show();
        this.$refs["inboxRulesRowButtons-" + index].setMargin(scrolledWidth);
      },
      onTrMouseleave(row, index) {
        this.$refs["inboxRulesRowButtons-" + index].close();
      },
      onCreateRule() {
        this.$router.push({name: "new"});
      },
      onEditRule(row) {
        this.$router.push({name: "edit", params: {id: row.id}});
      },
      onRemoveRule(row) {
        ProcessMaker.apiClient.delete("/tasks/rules/" + row.id)
                .then(response => {
                  let message = "The inbox rule '{{name}}' was removed.";
                  message = this.$t(message, {name: row.name});
                  ProcessMaker.alert(message, "success");
                  this.requestData();
                })
                .catch((err) => {
                  let message = "The operation cannot be performed. Please try again later.";
                  ProcessMaker.alert(this.$t(message), "danger");
                });
      },
      convertUTCToPMFormat(value) {
        if (!moment(value).isValid()) {
          return "N/A";
        }
        let timezone = ProcessMaker.user.timezone;
        let config = ProcessMaker.user.datetime_format;
        return moment(value).tz(timezone).format(config);
      },
      onChangeStatus(value, row) {
        let params = {
          active: value
        };
        ProcessMaker.apiClient.put("/tasks/rules/" + row["id"] + "/update-active", params)
                .then(response => {
                  let message = value ? "Rule activated" : "Rule deactivated";
                  ProcessMaker.alert(this.$t(message), "success");
                })
                .catch((err) => {
                  row["active"] = !value;
                  let message = "The operation cannot be performed. Please try again later.";
                  ProcessMaker.alert(this.$t(message), "danger");
                });
      }
    }
  };
</script>