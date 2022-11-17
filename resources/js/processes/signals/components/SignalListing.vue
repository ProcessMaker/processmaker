<template>
  <div class="data-table">
    <data-loading
      :for="/signals\?page/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card" data-cy="signals-table">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        :noDataTemplate="$t('No Data Available')"
      >
        <template slot="name" slot-scope="props">
          <span v-uni-id="props.rowData.id.toString()">{{props.rowData.name }}</span>
        </template>
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <span v-b-tooltip.hover
                    :title="isEditable(props.rowData) ? $t('Edit') : $t('Cannot edit system signals.')">
                <b-btn
                    variant="link"
                    @click="onEdit(props.rowData, props.rowIndex)"
                    :disabled="!isEditable(props.rowData)"
                    v-if="permission.includes('edit-processes')"
                    v-uni-aria-describedby="props.rowData.id.toString()"
                    :aria-label="$t('Edit') + ' ' + props.rowData.name.toString()"
                >
                    <i class="fas fa-pen-square fa-lg fa-fw"></i>
                </b-btn>
              </span>
              <span v-b-tooltip.hover
                :title="getDeleteButtonTitle(props.rowData)">
                <b-btn
                    variant="link"
                    @click="onReview(props.rowData, props.rowIndex)"
                    :disabled="(!isDeletable(props.rowData) || !permission.includes('edit-processes')) && !isCollection(props.rowData)"
                    v-uni-aria-describedby="props.rowData.id.toString()"
                    :aria-label="$t('Delete') + ' ' + props.rowData.name.toString()"
                >
                    <i v-if="isCollection(props.rowData)" class="fas fa-external-link-alt fa-lg fa-fw"></i>
                    <i v-else class="fas fa-trash-alt fa-lg fa-fw"></i>
                </b-btn>
              </span>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Signal')"
        :plural="$t('Signals')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "permission"],
  data() {
    return {
      orderBy: "id",

      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id",
        },
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
          sortField: "Name",
        },
        {
          title: () => this.$t("Actions"),
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  methods: {
    isDeletable(data) {
      let catches = data.processes.reduce((carry, process) => carry + process.catches.length, 0);
      return catches === 0;
    },
    isEditable(data) {
      let editable = true;
      data.processes.forEach(process => {
        if (process.catches.length && process.is_system) {
          editable = false;
        }
      });
      return editable;
    },
    getDeleteButtonTitle(rowData) {
        if (this.isCollection(rowData)) {
            return this.$t('View Collection');
        }
        if (!this.isDeletable(rowData) && this.isEditable(rowData)) {
            return this.$t('Cannot delete signals present in a process.');
        }
        if (!this.isDeletable(rowData) && !this.isEditable(rowData)) {
            return this.$t('Cannot delete system signals.');
        }
        if (!this.permission.includes('edit-processes')) {
            return this.$t('You do not have permission to delete signals');
        }
        return this.$t('Delete');
    },
    onEdit(data, index) {
      window.location = "/designer/signals/" + data.id + "/edit";
    },
    getIdCollection(data) {
      return data.id.replace('collection_', '')
                .replace('_create', '')
                .replace('_update', '')
                .replace('_delete', '');
    },
    isCollection(data) {
      return data.type === 'collection';
    },
    onReview(data, index) {
      if (this.isCollection(data)) {
        window.location = "/collections/" + this.getIdCollection(data);
        return;
      }
      this.onDelete(data, index);
    },
    onDelete(data, index) {
      let that = this;
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        "<b>" +
          this.$t("Are you sure you want to delete {{item}}?", {
            item: data.name,
          }) +
          "</b>",
        "",
        function () {
          ProcessMaker.apiClient
            .delete("signals/" + data.id)
            .then((response) => {
              ProcessMaker.alert(this.$t("The signal was deleted."), "success");
              that.fetch();
            });
        }
      );
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "signals?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
  },
};
</script>
