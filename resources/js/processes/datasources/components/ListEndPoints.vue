<template>
    <div class="data-table">
        <div class="card card-body table-card">
            <vuetable
                :dataManager="dataManager"
                :sortOrder="sortOrder"
                :css="css"
                :api-mode="false"
                :fields="fields"
                :data="data"
                data-path="data"
                :noDataTemplate="$t('No Data Available')"
                detail-row-component="detail-end-point"
                @vuetable:cell-clicked="detail"
                ref="endpoints"
            >
                <template slot="actions" slot-scope="props">
                    <div class="actions">
                        <div class="popout">
                            <b-btn
                                variant="link"
                                @click="detail(props.rowData)"
                                v-b-tooltip.hover
                                :title="$t('Details')">
                                <i v-if="!props.rowData.view" class="fas fa-search-plus fa-lg fa-fw"></i>
                                <i v-else class="fas fa-search-minus fa-lg fa-fw"></i>
                            </b-btn>
                            <b-btn
                                variant="link"
                                @click="test(props.rowData)"
                                v-b-tooltip.hover
                                :title="$t('Test')"
                            >
                                <i class="fas fa-play fa-lg fa-fw"></i>
                            </b-btn>
                            <b-btn
                                variant="link"
                                @click="doDelete(props.rowData)"
                                v-b-tooltip.hover
                                :title="$t('Remove')"
                            >
                                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
                            </b-btn>
                        </div>
                    </div>
                </template>
            </vuetable>
        </div>
    </div>
</template>

<script>
  import Vue from "vue";
  import datatableMixin from "../../../components/common/mixins/datatable";
  import DetailEndPoint from "../components/DetailEndPoint";

  Vue.component("detail-end-point", DetailEndPoint);

  export default {
    mixins: [datatableMixin],
    props: {
      filter: {
        type: String,
        default: ""
      },
      endpoints: {
        type: Object,
        default: {}
      },
      datasource: {
        type: Object,
        default: {}
      }
    },
    watch: {
      endpoints: {
        deep: true,
        handler () {
          const endpoints = {};
          Object.keys(this.endpoints)
            .forEach((name) => {
              endpoints[this.endpoints[name].purpose] = this.endpoints[name];
            });
          if (Object.keys(endpoints)
            .join(",") !== Object.keys(this.endpoints)
            .join(",")) {
            Object.keys(this.endpoints)
              .forEach(name => {
                this.$delete(this.endpoints, name);
              });
            Object.keys(endpoints)
              .forEach(name => {
                this.$set(this.endpoints, name, endpoints[name]);
              });
          }
        },
      },
    },
    data () {
      return {
        orderBy: "method",
        sortOrder: [
          {
            field: "method",
            sortField: "method",
            direction: "asc"
          }
        ],
        fields: [
          {
            title: () => this.$t("Purpose"),
            name: "purpose",
            sortField: "purpose"
          },
          {
            title: () => this.$t("Method"),
            name: "method",
            sortField: "method"
          },
          {
            title: () => this.$t("Url"),
            name: "url",
            sortField: "url"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },
    methods: {
      test (data, requester) {
        data.view = true;
        this.$refs.endpoints.showDetailRow(data.id);
        window.ProcessMaker.apiClient
          .post(
            `/datasources/${this.datasource.id}/test`,
            {
              data
            }
          );
      },
      fetch () {
        this.data = [];
        if (!this.endpoints) {
          return;
        }
        let index = 0;
        for (let name in this.endpoints) {
          let item = this.endpoints[name];
          item.view = false;
          item.id = index;
          index++;
          this.data.push(item);
        }
      },
      detail (data) {
        data.view = !data.view;
        this.$refs.endpoints.toggleDetailRow(data.id);
      },
      doDelete (item) {
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          "<b>" +
          this.$t("Are you sure you want to delete {{item}}?", {
            item: item.purpose
          }) +
          "</b>",
          "",
          () => {
            delete this.endpoints[item.purpose];
            this.fetch();
          }
        );
      }
    },
    mounted () {
      let userID = document.head.querySelector("meta[name=\"user-id\"]");
      window.Echo.private(
        `ProcessMaker.Models.User.${userID.content}`
      )
        .notification(response => {
          if (response.type === 'ProcessMaker\\Notifications\\DatasourceResponseNotification') {
            this.$refs.endpoints.$children.forEach(item => {
              if (item.rowIndex === response.index) {
                item.testResponse = response.response;
              }
            })
          }
        });
    },
  };
</script>
