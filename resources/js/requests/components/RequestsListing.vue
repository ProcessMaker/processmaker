<template>
  <div class="data-table">
    <data-loading
            :for=/requests\?page/
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader"  class="card card-body table-card">
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
        ref="vuetable"
      >
        <template slot="ids" slot-scope="props">
          <b-link :href="openRequest(props.rowData, props.rowIndex)">#{{props.rowData.id}}</b-link>
        </template>
        <template slot="participants" slot-scope="props">
          <avatar-image
            v-for="participant in props.rowData.participants"
            :key="participant.id"
            class="d-inline-flex pull-left align-items-center"
            size="25"
            hide-name="true"
            :input-data="participant"
          ></avatar-image>
        </template>
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                :href="openRequest(props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Open Request')"
              >
                <i class="fas fa-caret-square-right fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Request')"
        :plural="$t('Requests')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading.js";
import AvatarImage from "../../components/AvatarImage";
import isPMQL from "../../modules/isPMQL";
import moment from "moment";

Vue.component("avatar-image", AvatarImage);

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["filter", "columns", "pmql"],
  data() {
    return {
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc"
        }
      ],
      fields: []
    };
  },
  mounted() {
    this.setupColumns();
  },
  methods: {
    setupColumns() {
      let columns = this.getColumns();
      
      columns.forEach(column => {
        let field = {
          title: this.$t(column.label)
        };
        
        switch (column.field) {
          case 'id':
            field.name = '__slot:ids';
            field.title = '#';
            break;
          case 'participants':
            field.name = '__slot:participants';
            break;
          default:
            field.name = column.field;
        }
        
        if (!field.field) {
          field.field = column.field;
        }
        
        if (column.sortable && ! field.sortField) {
          field.sortField = column.field;
        }
        
        this.fields.push(field);
      });
      
      this.fields.push({
        name: "__slot:actions",
        title: ""
      });

      // this is needed because fields in vuetable2 are not reactive
      this.$nextTick(()=>{
        this.$refs.vuetable.normalizeFields();
      });
    },
    getColumns() {
      if (this.$props.columns) {
        return this.$props.columns;
      } else {
        return [
          {
            "label": "#",
            "field": "id",
            "sortable": true,
            "default": true
          },
          {
            "label": "Name",
            "field": "name",
            "sortable": true,
            "default": true
          },
          {
            "label": "Status",
            "field": "status",
            "sortable": true,
            "default": true
          },
          {
            "label": "Participants",
            "field": "participants",
            "sortable": false,
            "default": true
          },
          {
            "label": "Started",
            "field": "initiated_at",
            "sortable": true,
            "default": true
          },
          {
            "label": "Completed",
            "field": "completed_at",
            "sortable": true,
            "default": true
          }          
        ];
      }
    },
    openRequest(data, index) {
      return "/requests/" + data.id;
    },
    formatStatus(status) {
      let color = "success",
        label = "In Progress";
      switch (status) {
        case "DRAFT":
          color = "danger";
          label = "Draft";
          break;
        case "CANCELED":
          color = "danger";
          label = "Canceled";
          break;
        case "COMPLETED":
          color = "primary";
          label = "Completed";
          break;
        case "ERROR":
          color = "danger";
          label = "Error";
          break;
      }
      return (
        '<i class="fas fa-circle text-' +
        color +
        '"></i> <span>' +
        this.$t(label) +
        "</span>"
      );
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      for (let record of data.data) {
        //Format dates
        record["initiated_at"] = this.formatDate(record["initiated_at"]);
        if (record["completed_at"]) {
          record["completed_at"] = this.formatDate(record["completed_at"]);
        } else {
          record["completed_at"] = "";
        }
        //format Status
        record["status"] = this.formatStatus(record["status"]);
      }
      return data;
    },
    fetch(query, resetPagination) {
        Vue.nextTick(() => {
            if (resetPagination) {
              this.page = 1;
            }
            
            let pmql = '';
            
            if (this.pmql !== undefined) {
                pmql = this.pmql;
            }
                    
            let filter = this.filter;
            
            if (query && query.length) {
              if (query.isPMQL()) {
                pmql = `(${pmql}) and (${query})`;
              } else {
                filter = query;
              }
            }

            // Load from our api client
            ProcessMaker.apiClient
              .get(
                "requests?page=" +
                  this.page +
                  "&per_page=" +
                  this.perPage +
                  "&include=process,participants,data" +
                  "&pmql=" +
                  encodeURIComponent(pmql) +
                  "&filter=" +
                  filter +
                  "&order_by=" +
                  (this.orderBy === "__slot:ids" ? "id" : this.orderBy) +
                  "&order_direction=" +
                  this.orderDirection +
                  this.additionalParams
              )
              .then(response => {
                this.data = this.transform(response.data);
              }).catch(error => {
                if (_.has(error, 'response.data.message')) {
                  ProcessMaker.alert(error.response.data.message, 'danger');
                } else {
                  throw error;
                }
              });
            
        });
    }
  }
};
</script>
<style>
</style>
