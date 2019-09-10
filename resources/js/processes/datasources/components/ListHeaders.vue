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
      >
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="edit(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Edit')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
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
  import datatableMixin from "../../../components/common/mixins/datatable";

  export default {
    mixins: [datatableMixin],
    props: ['filter', 'info'],
    data() {
      return {
        orderBy: "key",
        sortOrder: [
          {
            field: "key",
            sortField: "key",
            direction: "asc"
          }
        ],
        fields: [
          {
            title: () => this.$t("Key"),
            name: "key",
            sortField: "key"
          },
          {
            title: () => this.$t("Value"),
            name: "value",
            sortField: "value"
          },
          {
            title: () => this.$t("Description"),
            name: "description",
            sortField: "description"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },
    watch: {
      info: {
        handler() {
          console.log('info data');
          this.data = this.info;
        }
      }
    },
    methods: {
      fetch() {
        console.log('fetch data');
        //
      },
      edit(row) {
        //
      },
      doDelete(item) {
        ProcessMaker.confirmModal(
          this.$t("Caution!"),
          this.$t("Are you sure you want to delete Data Source") + ' ' +
          item.name +
          this.$t("?"),
          "",
          () => {
            ProcessMaker.apiClient
              .delete("datasources/" + item.id)
              .then(() => {
                ProcessMaker.alert(this.$t('The Data Source was deleted.'), 'success');
                this.fetch();
              });
          }
        );
      }
    }
  };
</script>
