<template>
  <div class="data-table">
    <div class="card card-body table-card">
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :per-page="10"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="grouped"
          slot-scope="props"
        >
          <div class="grouped">
            <div class="custom-control custom-switch">
              <input
                :id="'switch_' + props.rowData.id"
                v-model="userGroups"
                :value="props.rowData.id"
                type="checkbox"
                class="custom-control-input"
              >
              <label
                class="custom-control-label"
                :for="'switch_' + props.rowData.id"
              />
            </div>
          </div>
        </template>
        <template
          slot="name"
          slot-scope="props"
        >
          <label
            class="m-0"
            :for="'switch_' + props.rowData.id"
          >{{ props.rowData.name }}</label>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Group')"
        :plural="$t('Groups')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";

export default {
  mixins: [datatableMixin],
  props: ["filter", "currentGroups", "userId"],
  data() {
    return {
      userGroups: this.currentGroupsById(),
      perPage: 10,
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => "",
          name: "__slot:grouped",
        },
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
        },
        {
          title: () => this.$t("Description"),
          name: "description",
        },
      ],
      name: "",
      description: "",
      create_screen_id: "",
      read_screen_id: "",
      update_screen_id: "",
      curIndex: "",
      id: "",
      errors: {
        name: null,
      },
      rules: {},
      previousFilter: "",
      previousPage: 1,
    };
  },
  methods: {
    currentGroupsById() {
      const groups = [];
      this.currentGroups.forEach((group) => {
        groups.push(group.id);
      });
      return groups;
    },
    changePerPage(perPage) {
      this.perPage = perPage;
      this.fetch();
    },
    fetch() {
      this.loading = true;

      if (this.filter.length && !this.previousFilter.length) {
        this.previousPage = this.page;
        this.page = 1;
      } else if (!this.filter.length && this.previousFilter.length) {
        this.page = this.previousPage;
      }

      this.queries = [];
      this.currentGroups.forEach((item) => {
        this.queries.push(`id != ${item.id}`);
      });

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "group_members_available"
                        + "?member_type=ProcessMaker\\Models\\User"
                        + `&member_id=${
                          this.userId
                        }&page=${
                          this.page
                        }&per_page=${
                          this.perPage
                        }&filter=${
                          this.filter
                        }&order_by=`
                        + "assigned",
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
          this.previousFilter = this.filter;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
</style>
