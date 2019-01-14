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
        <template slot="secret" slot-scope="props">
          <b-btn
            variant="link"
            @click="copySecret(props.rowData.secret)"
            v-b-tooltip.hover
            title="Copy Client Secret To Clipboard"
          >
            <i class="fas fa-clipboard fa-lg fa-fw"></i>
          </b-btn>
          {{ props.rowData.secret.substr(0, 10) }}...
        </template>
      </vuetable>
      <textarea style="position: absolute; left: -1000px; top: -1000px" ref="copytext"></textarea>
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
      copytext: "",
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
          title: "Client Secret",
          name: "__slot:secret",
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
    },
    copySecret(secret) {
      this.$refs.copytext.value = secret
      this.$refs.copytext.select()
      document.execCommand('copy')
    },
    doIt() {
      console.log("DOING IT")
    }
  }
};
</script>