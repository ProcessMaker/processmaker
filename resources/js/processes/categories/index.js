import Vue from "vue";
import CategoriesListing from "./components/CategoriesListing";

new Vue({
  el: "#categories-listing",
  data: {
    filter: "",
    formData: null,
    errors: {},
    id: "",
    name: "",
    status: "ACTIVE",
    disabled: false,
    route: ProcessMaker.user.category.route
  },
  components: {
    CategoriesListing
  },
  methods: {
    reload () {
      this.disabled = false;
      this.$refs.list.fetch();
    },
    edit (value) {
      this.disabled = false;
      this.id = value.id;
      this.name = value.name;
      this.status = value.status;
      $("#createCategory").modal("show");
    },
    onClose () {
      this.disabled = false;
      this.name = "";
      this.status = "ACTIVE";
      this.errors = {};
    },
    onSubmit () {
      this.errors = {};
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      let method = "POST",
        url = this.route;
      if (this.id) {
        // Do an update
        method = "PUT";
        url = `${url}/${this.id}`;
      }
      ProcessMaker.apiClient({
        method,
        url,
        baseURL: "/",
        data: {
          name: this.name,
          status: this.status
        }
      })
        .then((response) => {
          $("#createCategory").modal("hide");
          this.loading = false;
          ProcessMaker.alert(this.$t("The category was saved."), "success");
          this.reload();
        }).catch((error) => {
          this.disabled = false;
          if (error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    }
  }
});
