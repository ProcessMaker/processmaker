import Vue from "vue";
import AuthClientsListing from "./components/AuthClientsListing.vue";

Vue.component("AuthClientsListing", AuthClientsListing);

new Vue({
  el: "#authClients",
  data: {
    authClient: {
      id: null,
      name: "",
      types: [],
      redirect: "",
      secret: "",
    },
    filter: "",
    errors: null,
    disabled: false,
    title: "",
    secretTitle: "",
    customModalButtons: [],
    secret: "",
  },
  beforeMount() {
    this.initCustomModalButtons();
    this.resetValues();
  },
  methods: {
    onClose() {
      this.resetValues();
    },
    onSave() {
      if (this.disabled) {
        return;
      }
      this.disabled = true;

      this.loading = true;
      let method = "POST";
      let url = "/oauth/clients";
      let verb = "created";
      if (this.authClient.id) {
        method = "PUT";
        url = `${url}/${this.authClient.id}`;
        verb = "saved";
      }
      ProcessMaker.apiClient({
        method,
        url,
        baseURL: "/",
        data: this.authClient,
      }).then((response) => {
        this.$refs.createEditAuthClient.hide();
        this.loading = false;
        if (response.data.secret) {
          this.secret = response.data.secret;
          this.$refs.secretModal.show();
        } else {
          this.$refs.authClientList.fetch();
        }
        ProcessMaker.alert(`${this.$t("The auth client was ")}${verb}.`, this.$t("success"));
      }).catch((error) => {
        this.disabled = false;
        this.errors = error.response.data.errors;
      });
    },
    resetValues() {
      this.title = this.$t("Create Auth-Client");
      this.secretTitle = this.$t("Copy Secret To Clipboard");
      this.authClient = {
        id: null,
        name: "",
        types: [],
        redirect: "",
        secret: "",
      };
      this.errors = {
        name: null,
        redirect: null,
        types: null,
      };
      this.disabled = false;
      this.initCustomModalButtons();
    },
    edit(item) {
      this.title = this.$t("Edit Auth Client");
      this.authClient = item;
      this.$refs.createEditAuthClient.show();
    },
    initCustomModalButtons() {
      this.customModalButtons = [
        {
          content: "Close",
          action: "close",
          variant: "secondary",
          disabled: false,
          hidden: false,
        },
      ];
    },
    hideSecretModal() {
      this.$refs.secretModal.hide();
      this.$refs.authClientList.fetch();
    },
    copySecret(secret) {
      navigator.clipboard.writeText(secret).then(() => {
        ProcessMaker.alert(this.$t("Secret copied to clipboard."), "success");
      }, () => {
        ProcessMaker.alert(this.$t("Secret not copied to clipboard."), "danger");
      });
    },
  },
});
