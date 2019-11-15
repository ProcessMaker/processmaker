import Vue from "vue";
import ScriptListing from "./components/ScriptListing";
import CategorySelect from "../categories/components/CategorySelect";

Vue.component('category-select', CategorySelect);

new Vue({
    el: "#scriptIndex",
    data: {
        filter: "",
        addScript: {
            title: '',
            language: '',
            description: '',
            script_category_id: '',
            code: '',
            addError: {},
            selectedUser: '',
            users: [],
            timeout: 60,
        }
    },
    components: {
        ScriptListing
    },
    methods: {
        __(variable) {
            return __(variable);
        },
        deleteScript(data) {
            ProcessMaker.apiClient.delete(`scripts/${data.id}`)
                .then((response) => {
                    ProcessMaker.alert(this.$t("The script was deleted."), "success");
                    this.reload();
                });
        },
        reload() {
            this.$refs.listScript.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        },
        onClose() {
            this.addScript.title = '';
            this.addScript.language = '';
            this.addScript.description = '';
            this.addScript.script_category_id = '';
            this.addScript.code = '';
            this.addScript.timeout = 60;
            this.addScript.addError = {};
          },
          onSubmit(bvModalEvt) {
            bvModalEvt.preventDefault();
            this.addScript.errors = Object.assign({}, {
                name: null,
                description: null,
                status: null,
                script_category_id: null
            });

            ProcessMaker.apiClient.post("/scripts", {
                title: this.addScript.title,
                language: this.addScript.language,
                description: this.addScript.description,
                script_category_id: this.addScript.script_category_id,
                run_as_user_id: this.addScript.selectedUser ? this.addScript.selectedUser.id : null,
                code: "[]",
                timeout: this.addScript.timeout
            })
            .then(response => {
                ProcessMaker.alert(this.$t('The script was created.'), 'success');
                window.location = "/designer/scripts/" + response.data.id + "/builder";
            })
            .catch(error => {
                if (error.response.status && error.response.status === 422) {
                    this.addScript.addError = error.response.data.errors;
                }
            })
        }
    }
});
