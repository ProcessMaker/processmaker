<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Output Documents">
      <form>

        <div class="form-group">
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="destinationPath">
            <button type="submit" class="btn inline-button text-light"><i class="fas fa-plus fa-md"></i> Create</button>
          </div>
            <div class="data-table">
                <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta">
                    <template slot="actions" slot-scope="props"> 
                        <div class="actions">
                            <i class="fas fa-ellipsis-h"></i>
                            <div class="popout">
                            <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
                            <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
                            <b-btn variant="action" @click="onAction('users-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Users"><i class="fas fa-users"></i></b-btn>
                            <b-btn variant="action" @click="onAction('permissions-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Permissions"><i class="fas fa-user-lock"></i></b-btn>
                            </div>
                        </div>
                    </template>  
                </vuetable> 
                <pagination single="Role" plural="Roles" :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
            </div>
        </div>

    </form>

    <template slot="modal-footer">
      <b-button @click="onCancel" class="btn-outline-secondary btn-md">
        CANCEL
      </b-button>
      <b-button class="btn-secondary text-light btn-md">
        SAVE
      </b-button>
    </template>

  </b-modal>
</template>

<script>
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../../components/common/Pagination";
import datatableMixin from "../../../components/common/mixins/datatable";

export default {
    mixins: [datatableMixin],
    props: ["filter"],
  data() {
    return {
      // form models here
      'messageFieldName': "Name",
          items: [
      { message: 'Foo' },
      { message: 'Bar' }
    ],
    orderBy: "code",

      sortOrder: [
        {
          field: "ID",
          sortField: "id",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "ID",
          name: "id",
          sortField: "id"
        },
        {
          title: "Title",
          name: "title",
          sortField: "title"
        },
        {
          title: "Type",
          name: "type",
          sortField: "type"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },
  methods:{
    onHidden() {
      this.$emit('hidden')
    },
    onCancel() {
      this.$refs.modal.hide()
    }
  },
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  },
      formatActiveUsers(value) {
      return '<div class="text-center">' + value + "</div>";
    },
    formatStatus(value) {
      value = value.toLowerCase();
      let response = '<i class="fas fa-circle ' + value + '"></i> ';
      value = value.charAt(0).toUpperCase() + value.slice(1);
      return response + value;
    },
    fetch() {
      this.loading = true;
      if (this.cancelToken) {
        this.cancelToken();
        this.cancelToken = null;
      }
      const CancelToken = ProcessMaker.apiClient.CancelToken;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "roles?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection,
          {
            cancelToken: new CancelToken(c => {
              this.cancelToken = c;
            })
          }
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        })
        .catch(error => {
          // Undefined behavior currently, show modal?
        });
    }
};


</script>
<style lang="scss" scoped>

.inline-input{
  margin-right: 6px;
}
.inline-button{
  background-color: rgb(109,124,136);
  font-weight: 100;
}
.input-and-select{
  width:212px;
}
.sub-header {
  background-color: rgb(234, 236, 241);
  margin-top: 10px;
  border-radius: 2px;
}
.field-name {
  color: black;
  font-weight: bold;
  padding: 10px;
  margin-bottom: 0px;
}
.field-name-li { 
  display: flex;
  justify-content: space-between;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.2);
  color: rgb(109,124,136);
  font-size: 12px;
}

</style>
