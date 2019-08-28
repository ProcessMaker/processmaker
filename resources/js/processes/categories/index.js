import Vue from "vue";
import CategoriesListing from "./components/CategoriesListing";

new Vue({
  el: "#categories-listing",
  data: {
    filter: "",
    formData: null
  },
  components: {
    CategoriesListing
  },
  methods: {
    editCategory(data) {
      this.formData = Object.assign({}, data);
      this.showModal();
    },
    showModal() {
      this.$refs.addEdit.$refs.modal.show();
    },
    deleteCategory(data) {
      ProcessMaker.apiClient.delete(`${window.Processmaker.delete}/${data.id}`)
        .then((response) => {
          ProcessMaker.alert("The category was deleted.", "success");
          this.reload();
        });
    },
    reload() {
      this.$refs.list.dataManager([
        {
          field: "updated_at",
          direction: "desc"
        }
      ]);
    }
  }
});
