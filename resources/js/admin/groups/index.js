import Vue from "vue";

import GroupsListing from "./components/GroupsListing.vue";

Vue.component("GroupsListing", GroupsListing);

new Vue({
  el: "#listGroups",
  data() {
    return {
      filter: "",
      formData: {},
      errors: {
        name: null,
        description: null,
        status: null,
      },
      disabled: false,
    };
  },
  mounted() {
    this.resetFormData();
    this.resetErrors();
  },
  methods: {
    reload() {
      this.$refs.groupList.dataManager([
        {
          field: "updated_at",
          direction: "desc",
        },
      ]);
    },
    onClose() {
      this.resetFormData();
      this.resetErrors();
    },
    resetFormData() {
      this.formData = Object.assign({}, {
        name: null,
        description: null,
        status: "ACTIVE",
      });
    },
    resetErrors() {
      this.errors = Object.assign({}, {
        name: null,
        description: null,
        status: null,
      });
    },
    onSubmit() {
      this.resetErrors();
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.post("groups", this.formData)
        .then((response) => {
          ProcessMaker.alert(this.$t("The group was created."), "success");
          window.location = `/admin/groups/${response.data.id}/edit`;
        })
        .catch((error) => {
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
          this.disabled = false;
        });
    },
  },
});
