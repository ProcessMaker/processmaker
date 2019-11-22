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
    emptyData () {
      this.id = "";
      this.name = "";
      this.status = "ACTIVE";
      this.disabled = false;
      this.errors = {};
    },
    getTitle () {
      return this.id ? this.$t("Edit Category") : this.$t("Create Category");
    },
    reload () {
      this.$refs.list.fetch();
    },
    edit (value) {
      this.emptyData();
      this.id = value.id;
      this.name = value.name;
      this.status = value.status;
      $("#createCategory").modal("show");
    },
    onClose () {
      this.emptyData();
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
          let message = "The category was created.";
          if (this.id) {
            message = "The category was saved.";
          }
          ProcessMaker.alert(this.$t(message), "success");
          this.emptyData();
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
