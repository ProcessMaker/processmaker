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
            v-if="permission.includes('edit-scripts')"
            @click="onAction('edit-script', props.rowData, props.rowIndex)"
          >{{props.rowData.title}}</b-link>
          <span v-else="permission.includes('edit-scripts')">{{props.rowData.title}}</span>
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit-script', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="__('Edit')"
                v-if="permission.includes('edit-scripts')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('edit-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="__('Configure')"
                v-if="permission.includes('edit-scripts')"
              >
                <i class="fas fa-cog fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('duplicate-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="__('Duplicate')"
                v-if="permission.includes('create-scripts')"
              >
                <i class="fas fa-copy fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('remove-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="__('Delete')"
                v-if="permission.includes('delete-scripts')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        single="Script"
        plural="Scripts"
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
            v-model="dupScript.title"
            v-bind:class="{ 'is-invalid': errors.title }"
          >
          <div class="invalid-feedback" v-if="errors.title">{{errors.title[0]}}</div>
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea class="form-control" id="description" rows="3" v-model="dupScript.description"></textarea>
        </div>
        <div class="form-group">
          <label for="type">Language</label>
          <select class="form-control" id="type" disabled>
            <option>{{dupScript.language}}</option>
          </select>
        </div>
      </form>
      <div slot="modal-footer" class="w-100" align="right">
        <button type="button" class="btn btn-outline-secondary" @click="hideModal">{{__('Cancel')}}</button>
        <button type="button" @click="onSubmit" class="btn btn-secondary ml-2">{{__('Save')}}</button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";
import __ from "../../../modules/lang";

export default {
  mixins: [datatableMixin],
  props: ["filter", "id", "permission", "scriptFormats"],
  data() {
    return {
      dupScript: {
        title: "",
        type: "",
        description: ""
      },
      errors: [],
      orderBy: "title",

      sortOrder: [
        {
          field: "title",
          sortField: "title",
          direction: "asc"
        }
      ],

      fields: [
        {
          title: __("Name"),
          name: "__slot:title",
          field: "title",
          sortField: "title"
        },
        {
          title: __("Description"),
          name: "description",
          sortField: "description"
        },
        {
          title: __("Language"),
          name: "language",
          sortField: "language",
          callback: this.formatLanguage
        },
        {
          title: __("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate"
        },
        {
          title: __("Created"),
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
    __(variable) {
      return __(variable);
    },
    goToEdit(data) {
      window.location = "/processes/scripts/" + data + "/edit";
    },
    showModal() {
      this.$refs.myModalRef.show();
    },
    hideModal() {
      this.$refs.myModalRef.hide();
    },
    onSubmit() {
      ProcessMaker.apiClient
        .put("scripts/" + this.dupScript.id + "/duplicate", this.dupScript)
        .then(response => {
          ProcessMaker.alert(__('The script was duplicated.'), "success");
          this.hideModal();
          this.fetch();
        })
        .catch(error => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
    onAction(action, data, index) {
      switch (action) {
        case "edit-script":
          window.location.href = "/processes/scripts/" + data.id + "/builder";
          break;
        case "edit-item":
          this.goToEdit(data.id);
          break;
        case "duplicate-item":
          this.dupScript.title = data.title + " Copy";
          this.dupScript.language = data.language;
          this.dupScript.code = data.code;
          this.dupScript.description = data.description;
          this.dupScript.id = data.id;
          this.showModal();
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            __("Caution!"),
            __("Are you sure you want to delete the script ") +
              data.title +
              __("?"),
            "",
            () => {
              this.$emit("delete", data);
            }
          );
          break;
          break;
      }
    },
    formatLanguage(language) {
      if (this.scriptFormats[language] !== undefined) {
        return this.scriptFormats[language];
      } else {
        return language.toUpperCase();
      }
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "scripts" +
            "?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=user"
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
</style>
