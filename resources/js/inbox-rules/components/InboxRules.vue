<template>
  <div>
    <PMTable
      :headers="headers"
      :data="responseData"
      :base-u-r-l="baseURL"
      empty-icon="noData"
      @onRowMouseover="onRowMouseover"
      @onTrMouseleave="onTrMouseleave"
      @onPageChange="onPageChange"
    >
      <template #top-content>
        <PMSearchBar v-model="filter">
          <template #right-content>
            <b-button
              class="ml-md-1 d-flex align-items-center text-nowrap"
              variant="primary"
              data-cy="createRule"
              @click="onCreateRule"
            >
              <img
                src="/img/plus-lg.svg"
                :alt="$t('Create Rule')"
              >
              {{ $t('Create Rule') }}
            </b-button>
          </template>
        </PMSearchBar>
      </template>

      <template #cell-active="{ row, header, rowIndex }">
        <b-form-checkbox
          v-model="row['active']"
          switch
          :data-cy="'statusSwitch'+rowIndex"
          @change="onChangeStatus($event,row)"
        />
      </template>

      <template #cell-created_at="{ row, header, rowIndex }">
        {{ convertUTCToPMFormat(row['created_at']) }}
      </template>

      <template #cell-end_date="{ row, header, rowIndex }">
        <InboxRulesRowButtons
          :ref="'inboxRulesRowButtons-'+rowIndex"
          :value="convertUTCToPMFormat(row['end_date'])"
          :row="row"
          :data-cy="'inboxRulesRowButtons'+rowIndex"
          @onEditRule="onEditRule"
          @onRemoveRule="onRemoveRule"
        />
      </template>

      <template #no-results>
        <PMMessageScreen>
          <template #content>
            <img
              src="/img/inbox-rule-suggest-lg.svg"
              :alt="$t('Inbox rules empty')"
            >
            <b class="no-rule-class-title">
              {{ $t("You haven't set up any Inbox Rules yet") }}
            </b>
            <span
              class="no-rule-class-text"
              v-html="$t('Inbox Rules act as your personal task manager. You tell them what to look for, and <b>they take care of things automatically</b>.')"
            />
            <a
              href="#"
              @click="onCreateRule"
            >
              {{ $t("Create an Inbox Rule Now") }}
            </a>
          </template>
        </PMMessageScreen>
      </template>
    </PMTable>
  </div>
</template>

<script>
import PMTable from "../../components/PMTable.vue";
import PMSearchBar from "../../components/PMSearchBar.vue";
import InboxRulesRowButtons from "./InboxRulesRowButtons.vue";
import PMMessageScreen from "../../components/PMMessageScreen.vue";

export default {
  components: {
    PMTable,
    PMSearchBar,
    InboxRulesRowButtons,
    PMMessageScreen,
  },
  data() {
    return {
      responseData: { data: [], meta: {} },
      headers: this.columns(),
      baseURL: "tasks/rules",
      page: 1,
      per_page: 10,
      order_by: "name",
      order_direction: "asc",
      filter: "",
    };
  },
  watch: {
    page() {
      this.requestData();
    },
    filter() {
      this.requestData();
    },
  },
  mounted() {
    this.requestData();
  },
  methods: {
    columns() {
      return [
        {
          label: this.$t("Name"),
          field: "name",
          width: 10,
        },
        {
          label: this.$t("Status"),
          field: "active",
          width: 10,
        },
        {
          label: this.$t("Creation Date"),
          field: "created_at",
          width: 10,
        },
        {
          label: this.$t("Deactivation Date"),
          field: "end_date",
          width: 10,
        },
      ];
    },
    requestData() {
      const url = `${this.baseURL}?`
                + `page=${this.page}&`
                + `per_page=${this.per_page}&`
                + `order_by=${this.order_by}&`
                + `order_direction=${this.order_direction}&`
                + `filter=${this.filter}`;

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
      this.$refs[`inboxRulesRowButtons-${index}`].show();
      this.$refs[`inboxRulesRowButtons-${index}`].setMargin(scrolledWidth);
    },
    onTrMouseleave(row, index) {
      this.$refs[`inboxRulesRowButtons-${index}`].close();
    },
    onCreateRule() {
      this.$router.push({ name: "new" });
    },
    onEditRule(row) {
      this.$router.push({ name: "edit", params: { id: row.id } });
    },
    onRemoveRule(row) {
      ProcessMaker.apiClient.delete(`/tasks/rules/${row.id}`)
        .then((response) => {
          let message = "The inbox rule '{{name}}' was removed.";
          message = this.$t(message, { name: row.name });
          ProcessMaker.alert(message, "success");
          this.requestData();
        })
        .catch((err) => {
          const message = "The operation cannot be performed. Please try again later.";
          ProcessMaker.alert(this.$t(message), "danger");
        });
    },
    convertUTCToPMFormat(value) {
      if (!moment(value).isValid()) {
        return "N/A";
      }
      const { timezone } = ProcessMaker.user;
      const config = ProcessMaker.user.datetime_format.split(" ")[0];
      return moment.utc(value).tz(timezone).format(config);
    },
    onChangeStatus(value, row) {
      const params = {
        active: value,
      };
      ProcessMaker.apiClient.put(`/tasks/rules/${row.id}/update-active`, params)
        .then((response) => {
          const message = value ? "Rule activated" : "Rule deactivated";
          ProcessMaker.alert(this.$t(message), "success");
        })
        .catch((err) => {
          row.active = !value;
          const message = "The operation cannot be performed. Please try again later.";
          ProcessMaker.alert(this.$t(message), "danger");
        });
    },
  },
};
</script>
<style scoped>
.no-rule-class-title {
  color: #556271;
  font-size: 24px;
}
.no-rule-class-text {
  color: #556271;
  font-size: 16px;
}
</style>
