import Vue from "vue";
import VuePassword from "vue-password";
import UsersListing from "./components/UsersListing";
import AddUserModal from "./components/AddUserModal";
import DeletedUsersListing from "./components/DeletedUsersListing";

Vue.component("VuePassword", VuePassword);

new Vue({
  el: "#users-listing",
  components: { UsersListing, AddUserModal },
  data: {
    filter: "",
    config: {
      username: "",
      firstname: "",
      lastname: "",
      title: "",
      status: "",
      email: "",
      password: "",
      confpassword: "",
      addError: {},
      submitted: false,
      disabled: false,
    },
    configCopy: null,
    focusErrors: "config.addError",
  },
  mounted() {
    this.$root.$on("bv::modal::hide", () => {
      if (this.configCopy) {
        const tempConfig = _.cloneDeep(this.configCopy);
        this.configCopy = null;
        this.config = tempConfig;
      }
      this.resetForm();
    });

    this.$root.$on("updateErrors", (val) => {
      this.config.addError = val;
    });
  },
  methods: {
    resetForm() {
      this.config = {
        username: "",
        firstname: "",
        lastname: "",
        title: "",
        status: "",
        email: "",
        password: "",
        confpassword: "",
        addError: {},
        submitted: false,
        disabled: false,
      };
    },
    reload() {
      this.$refs.listing.dataManager([{
        field: "updated_at",
        direction: "desc",
      }]);
    },
    validatePassword() {
      if (this.config.password !== this.config.confpassword) {
        if (!("password" in this.config.addError)) {
          this.$set(this.config.addError, "password", null);
        }
        this.config.addError.password = ["Passwords must match"];
        this.$refs.passwordStrength.updatePassword("");
        this.config.password = "";
        this.config.confpassword = "";
        this.config.submitted = false;
        return false;
      }
      return true;
    },
    showModal() {
      this.configCopy = _.cloneDeep(this.config);
      this.$refs.addUserModal.showAddUserModal();
    },
    hideModal() {
      this.$refs.addUserModal.hideAddUserModal();
      this.configCopy = null;
      this.resetForm();
    },
    onSubmit() {
      this.config.addError = {};
      if (this.validatePassword()) {
        this.config.submitted = true;
        this.$refs.addUserModal.onSubmit(this.config);
      }
    },
    downloadAllLogs() {
      ProcessMaker.apiClient
        .get("security-logs/download/all?format=csv")
        .then(response => {
          window.ProcessMaker.alert(response.data.message, "success");
        });
    },
  },
});

new Vue({
  el: "#deleted-users-listing",
  components: { DeletedUsersListing },
  data: {
    filter: "",
  },
});
