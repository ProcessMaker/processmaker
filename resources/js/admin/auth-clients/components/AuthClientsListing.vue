<template>
  <div class="data-table">
    <div class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
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
        <template slot="secret" slot-scope="props">
          <b-btn
            variant="link"
            class="copylink"
            @click="copySecret(props.rowData.secret)"
            v-b-tooltip.hover
            :title="$t('Copy Client Secret To Clipboard')"
          >
            <i class="fas fa-clipboard fa-lg fa-fw"></i>
          </b-btn>
          {{ props.rowData.secret.substr(0, 10) }}...
        </template>
      </vuetable>
      <textarea class="copytext" ref="copytext"></textarea>
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

      fields: [
        {
          title: () => this.$t("Client ID"),
          name: "id"
        },
        {
          title: () => this.$t("Name"),
          name: "name"
        },
        {
          title: () => this.$t("Redirect"),
          name: "redirect",
          callback(val) {
            return val.substr(0, 20) + "...";
          }
        },
        {
          title: () => this.$t("Client Secret"),
          name: "__slot:secret"
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
        .get("/oauth/clients", { baseURL: "/" })
        .then(response => {
          this.data = response.data;
          this.loading = false;
        });
    },
    edit(row) {
      this.$emit("edit", Object.assign({}, row));
    },
    copySecret(secret) {
      this.$refs.copytext.value = secret;
      this.$refs.copytext.select();
      document.execCommand("copy");
    },
    doDelete(item) {
      ProcessMaker.confirmModal(
        $t("Caution!"),
        $t("Are you sure you want to delete the auth client ") +
          item.name +
          $t("?"),
        "",
        () => {
          ProcessMaker.apiClient
            .delete("/oauth/clients/" + item.id, { baseURL: "/" })
            .then(() => {
              ProcessMaker.alert($t('The auth client was deleted.'), 'success');
              this.fetch();
            });
        }
      );
    }
  }
};
</script>

<style>
.copytext {
  position: absolute;
  left: -1000px;
  top: -1000px;
}

.copylink {
  padding: 0;
}
</style>