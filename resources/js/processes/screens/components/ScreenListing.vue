<template>
  <div class="data-table">
    <div class="card card-body table-card">
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
      >
        <template slot="title" slot-scope="props">
          <b-link
            @click="onAction('edit-screen', props.rowData, props.rowIndex)"
            v-if="permission.includes('edit-screens')"
          >{{props.rowData.title}}</b-link>
          <span v-else="permission.includes('edit-screens')">{{props.rowData.title}}</span>
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit-screen', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Open Editor"
                v-if="permission.includes('edit-screens')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('edit-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Config"
                v-if="permission.includes('edit-screens')"
              >
                <i class="fas fa-cog fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('duplicate-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Duplicate"
                v-if="permission.includes('create-screens')"
              >
                <i class="fas fa-copy fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('remove-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Remove"
                v-if="permission.includes('delete-screens')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        single="Screen"
        plural="Screens"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
    <b-modal ref="myModalRef" title="Duplicate Screen" centered>
      <form>
        <div class="form-group">
          <label for="title">Name</label>
          <input
            type="text"
            class="form-control"
            id="title"
            v-model="dupScreen.title"
            v-bind:class="{ 'is-invalid': errors.title }"
          >
          <div class="invalid-feedback" v-if="errors.title">{{errors.title[0]}}</div>
        </div>
        <div class="form-group">
          <label for="type">Type</label>
          <select class="form-control" id="type" disabled>
            <option>{{dupScreen.type}}</option>
          </select>
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea class="form-control" id="description" rows="3" v-model="dupScreen.description"></textarea>
        </div>
      </form>
      <div slot="modal-footer" class="w-100" align="right">
        <button type="button" class="btn btn-outline-secondary" @click="hideModal">Close</button>
        <button type="button" @click="onSubmit" class="btn btn-secondary ml-2">Save</button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";

export default {
  mixins: [datatableMixin],
  props: ["filter", "id", "permission"],
  data() {
    return {
      orderBy: "title",
      dupScreen: {
        title: "",
        type: "",
        description: ""
      },
      errors: [],
      sortOrder: [
        {
          field: "title",
          sortField: "title",
          direction: "asc"
        }
      ],

      fields: [
        {
          title: "Name",
          name: "__slot:title",
          field: "title",
          sortField: "title"
        },
        {
          title: "Description",
          name: "description",
          sortField: "description"
        },
        {
          title: "Type",
          name: "type",
          sortField: "type"
        },
        {
          title: "Modified",
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate"
        },
        {
          title: "Created",
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },

  methods: {
    showModal() {
      this.$refs.myModalRef.show();
    },
    hideModal() {
      this.$refs.myModalRef.hide();
    },
    onSubmit() {
      ProcessMaker.apiClient
        .put("screens/" + this.dupScreen.id + "/duplicate", this.dupScreen)
        .then(response => {
          ProcessMaker.alert("The screen was duplicated.", "success");
          this.fetch();
        })
        .catch(error => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
    onAction(actionType, data, index) {
      switch (actionType) {
        case "edit-screen":
          window.location.href =
            "/processes/screen-builder/" + data.id + "/edit";
          break;
        case "edit-item":
          window.location.href = "/processes/screens/" + data.id + "/edit";
          break;
        case "duplicate-item":
          this.dupScreen.title = data.title + " Copy";
          this.dupScreen.type = data.type;
          this.dupScreen.description = data.description;
          this.dupScreen.id = data.id;
          this.showModal();
          break;
        case "remove-item":
          let that = this;
          ProcessMaker.confirmModal(
            "Caution!",
            "<b>Are you sure to delete the Screen </b>" + data.title + "?",
            "",
            function() {
              ProcessMaker.apiClient
                .delete("screens/" + data.id)
                .then(response => {
                  ProcessMaker.alert("Screen successfully deleted", "success");
                  that.fetch();
                });
            }
          );
          break;
      }
    },
    fetch() {
      this.loading = true;
      //change method sort by slot name
      this.orderBy = this.orderBy === "__slot:title" ? "title" : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "screens" +
            "?page=" +
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
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    }
  },

  computed: {}
};
</script>

<style lang="scss" scoped>
/deep/ th#_total_users {
  width: 150px;
  text-align: center;
}

/deep/ th#_description {
  width: 250px;
}

/deep/ .rounded-user {
  border-radius: 50% !important;
  height: 1.5em;
  margin-right: 0.5em;
}
</style>
