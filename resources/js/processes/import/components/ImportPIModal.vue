<template>
  <div>
    <modal
      id="importPI"
      :title="title"
      :subtitle="subtitle"
      :set-custom-buttons="true"
      :custom-buttons="buttons"
      data-test="import-pi-modal"
      size="lg"
      @importNewPI="importNewPI()"
      @hidden="onClose()"
    >
      <template>
        <div class="card-body">
          <div id="pre-import">
            <draggable-file-upload
              v-if="!file || file && !fileIsValid"
              ref="file"
              v-model="file"
              class="text-center"
              :options="{singleFile: true}"
              :display-uploader-list="false"
              :accept="['application/json']"
            />
            <div
              v-else
              class="text-left"
            >
              <h5> {{ $t("You are about to import") }} <strong>{{ processName }}</strong></h5>
              <div class="border-dotted py-3 d-flex text-center font-weight-bold my-3">
                {{ file.name }}
                <b-button
                  variant="link"
                  class="p-0"
                  aria-describedby=""
                  @click="removeFile"
                >
                  <i class="fas fa-times-circle text-danger" />
                </b-button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </modal>
  </div>
</template>
<script>
import Modal from "../../../components/shared/Modal.vue";
import DraggableFileUpload from "../../../components/shared/DraggableFileUpload.vue";

export default {
  components: { Modal, DraggableFileUpload },
  data() {
    return {
      file: "",
      uploaded: false,
      fileIsValid: false,
      processName: "",
      importDisabled: true,
    };
  },
  computed: {
    title() {
      return this.$t("Import Process");
    },
    subtitle() {
      return this.$t("Import a process from Process Intelligence to this ProcessMaker environment");
    },
    buttons() {
      return [
        {
          content: "Cancel", action: "hide()", variant: "outline-secondary", disabled: false, hidden: false,
        },
        {
          content: "Import", action: "importNewPI", variant: "primary", disabled: this.importDisabled, hidden: false,
        },
      ];
    },
  },
  watch: {
    file() {
      this.fileIsValid = false;
      if (!this.file) {
        this.importDisabled = true;
        return;
      }
      this.importDisabled = false;
      this.validatePIProcess();
      this.processName = this.file.name.split(".").slice(0, -1).toString();
    },
  },
  methods: {
    show() {
      this.$bvModal.show("importPI");
    },
    hide() {
      this.removeFile();
      this.$bvModal.hide("importPI");
    },
    onClose() {
      this.$emit("onClose");
    },
    validatePIProcess() {
      if (!this.file) {
        return;
      }
      this.$root.file = this.file;

      this.fileIsValid = true;
    },
    removeFile() {
      this.file = "";
      this.fileIsValid = false;
    },
    importNewPI() {
      if (!this.file) {
        return;
      }

      const formData = new FormData();
      formData.append("file", this.file);

      ProcessMaker.apiClient
        .post("/package-ai/pi_process/import", formData)
        .then((response) => {
          ProcessMaker.alert(this.$t("The PI process was created."), "success");
          window.location = `/modeler/${response.data.id}`;
        })
        .catch((error) => {
          window.ProcessMaker.alert(this.$t("An error ocurred, please check the PI process file and try again."), "danger");
          console.error(error);
        });
    },
  },
};
</script>
