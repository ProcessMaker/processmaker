import Vue from "vue";
import ProcessesListing from "./components/ProcessesListing";
import CategorySelect from "./categories/components/CategorySelect";

Vue.component('category-select', CategorySelect);

new Vue({
    el: "#processIndex",
    data: {
        filter: "",
        processModal: false,
        processId: null,
        processForm: {
            name: "",
            selectedFile: "",
            file: null,
            categoryOptions: "",
            description: "",
            process_category_id: "",
            addError: {},
            status: "",
            bpmn: "",
            disabled: false,
            errors: {},
        }
    },
    components: {
        ProcessesListing
    },
    methods: {
        show() {
            this.processId = null;
            this.processModal = true;
        },
        edit(id) {
            this.processId = id;
            this.processModal = true;
        },
        goToImport() {
            window.location = "/processes/import"
        },
        reload() {
            this.$refs.processListing.dataManager([{
                field: "updated_at",
                direction: "desc"
            }]);
        },



          browse () {
            this.$refs.customFile.click();
          },
          onFileChange (e) {
            let files = e.target.files || e.dataTransfer.files;

            if (!files.length) {
              return;
            }

            this.processForm.selectedFile = files[0].name;
            this.processForm.file = this.$refs.customFile.files[0];
          },
          onClose () {
            this.processForm.name = "";
            this.processForm.description = "";
            this.processForm.process_category_id = "";
            this.processForm.status = "";
            this.processForm.addError = {};
          },
          onSubmit (event) {
            event.preventDefault();
            this.processForm.errors = Object.assign({}, {
              name: null,
              description: null,
              process_category_id: null,
              status: null
            });
            if (this.processForm.process_category_id === "") {
              this.processForm.addError = {"process_category_id": [this.$t('The category field is required.')]};
              return;
            }
            //single click
            if (this.processForm.disabled) {
              return;
            }
            this.processForm.disabled = true;

            let formData = new FormData();
            formData.append("name", this.processForm.name);
            formData.append("description", this.processForm.description);
            formData.append("process_category_id", this.processForm.process_category_id);
            if (this.processForm.file) {
              formData.append("file", this.processForm.file);
            }

            ProcessMaker.apiClient.post("/processes", formData,
              {
                headers: {
                  "Content-Type": "multipart/form-data"
                }
              })
              .then(response => {
                ProcessMaker.alert(this.$t('The process was created.'), "success");
                window.location = "/modeler/" + response.data.id;
              })
              .catch(error => {
                this.processForm.disabled = false;
                this.processForm.addError = error.response.data.errors;
              });
          }
    }
});
