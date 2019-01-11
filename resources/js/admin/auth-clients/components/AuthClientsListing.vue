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
      >
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onEdit(props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Edit"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onDelete( props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Remove"
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
  props: ["filter"],
  data() {
    return {
      orderBy: "name",

      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "Name",
          name: "name",
          sortField: "Name"
        },
        {
          title: "Redirect",
          name: "redirect",
          sortField: "redirect",
          callback(val) { return val.substr(0, 20) + '...' }
        },
        {
          title: "Active",
          name: "revoked",
          sortField: "revoked",
          callback(val) { return !val }
        },
        {
          title: "Secret",
          name: "secret",
          callback(val) {
            return val.substr(0, 10) + '...'
          }
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },
  methods: {
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get('/oauth/clients', { baseURL: '/'})
        .then(response => {
          this.data = response.data;
          this.loading = false;
        });
    },
    onEdit(row, index) {
      this.$emit('edit', row);
    }
  }
};
</script>