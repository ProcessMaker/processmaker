import Vue from "vue";
import CategoriesListing from "./components/CategoriesListing";

new Vue({
    el: "#process-categories-listing",
    data: {
        filter: "",
        formData: null
    },
    components: {
        CategoriesListing
    },
    methods: {
        editCategory (data) {
            this.formData = Object.assign({}, data);
            this.showModal();
        },
        showModal () {
            this.$refs.addEdit.$refs.modal.show();
        },
        deleteCategory (data) {
            ProcessMaker.apiClient.delete(`process_categories/${data.id}`)
                .then((response) => {
                    ProcessMaker.alert("Category Successfully Deleted", "success");
                    this.reload();
                });
        },
        reload () {
            this.$refs.list.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        }
    }
});
