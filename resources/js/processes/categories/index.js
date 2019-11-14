import Vue from "vue";
import CategoriesListing from "./components/CategoriesListing";

new Vue({
  el: "#categories-listing",
  data: {
    filter: "",
    formData: null,
    addCategory: {
      errors: {},
      name: "",
      status: "ACTIVE",
      disabled: false,
    },
  },
  components: {
    CategoriesListing
  },
  methods: {
    reload () {
      this.$refs.list.fetch();
    },
    onClose () {
      this.addCategory.name = "";
      this.addCategory.status = "ACTIVE";
      this.addCategory.errors = {};
    },
    onSubmit () {
      this.errors = {};
      //single click
      if (this.addCategory.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.post('/process_categories', { 
        name: this.addCategory.name,
        status: this.addCategory.status
      })
      .then(response => {
        ProcessMaker.alert(this.$t('The category was created.'), 'success');
        this.reload();
      })
      .catch(error => {
        this.addCategory.disabled = false;
        if (error.response.status === 422) {
          this.addCategory.errors = error.response.data.errors;
        }
      });
    },
  }
});
