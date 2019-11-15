import Vue from "vue";
import VariablesListing from "./components/VariablesListing";

new Vue({
    el: "#process-variables-listing",
    data: {
        filter: "",
        addEnvVariable: {
            errors: {},
            name: '',
            description: '',
            value: '',
        }
    },
    components: {
        VariablesListing
    },
    methods: {
        __(variable) {
            return __(variable);
        },
        deleteVariable(data) {
            ProcessMaker.apiClient.delete(`environment_variables/${data.id}`)
                .then((response) => {
                    ProcessMaker.alert(this.$t("The environment variable was deleted."), "success");
                    this.reload();
                });
        },
        reload() {
            this.$refs.listVariable.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        },
        onClose() {
            this.addEnvVariable.name = '';
            this.addEnvVariable.description = '';
            this.addEnvVariable.value = '';
            this.addEnvVariable.errors = {};
        },
        onSubmit(bvModalEvt) {
            bvModalEvt.preventDefault();
            this.addEnvVariable.errors = {};
            ProcessMaker.apiClient.post('environment_variables', {
              name: this.addEnvVariable.name,
              description: this.addEnvVariable.description,
              value: this.addEnvVariable.value
            })
            .then(response => {
                ProcessMaker.alert(this.$t('The environment variable was created.'), 'success');
                this.reload();
                this.$refs.createEnvironmentVariable.hide();
            })
            .catch(error => {
                if (error.response.status === 422) {
                  this.addEnvVariable.errors = error.response.data.errors
                }
            });
        }
    }
});
