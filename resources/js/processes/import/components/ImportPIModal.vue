<template>
  <div>
    <modal
      id="importPI"
      :title="title"
      :subtitle="subtitle"
      :setCustomButtons="true"
      :customButtons="buttons"
      @importNewPI="importNewPI()"
      @hidden="onClose()"
      size="lg"
    >
      <template>
        <div class="card-body">
          <div id="pre-import">
            <draggable-file-upload class="text-center" v-if="!file || file && !fileIsValid" ref="file" v-model="file" :options="{singleFile: true}" :displayUploaderList="false" :accept="['image/png', 'image/jpg', 'image/jpeg']"></draggable-file-upload>
            <div v-else class="text-left">
              <h5> {{ $t("You are about to import") }} <strong>{{processName}}</strong></h5>
              <div class="border-dotted p-3 col-4 text-center font-weight-bold my-3">
                {{file.name}}
                <b-button 
                  variant="link" 
                  @click="removeFile"
                  class="p-0"
                  aria-describedby=""
                >
                  <i class="fas fa-times-circle text-danger"></i>
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
import DraggableFileUpload from '../../../components/shared/DraggableFileUpload.vue';

export default {
  components: { Modal, DraggableFileUpload },
  data() {
    return {
      file: '',
      uploaded: false,
      fileIsValid: false,
      processName: '',
      importDisabled: true,
    };
  },
  watch: {
    file() {
      this.fileIsValid = false;
      if (!this.file) {
        this.importDisabled = true;
        return
      }
      this.importDisabled = false;
      this.validatePIProcess();
      this.processName = this.file.name.split('.').slice(0,-1).toString();
    }
  },
  methods: {
    show() {
      this.$bvModal.show('importPI');
    },
    hide() {
      this.$bvModal.hide('importPI');
    },
    onClose() {
      this.$emit('onClose');
    },
    validatePIProcess() {
      if (!this.file) {
          return;
      }
      this.$root.file = this.file;

      let formData = new FormData();
      formData.append('file', this.file);

      this.fileIsValid = true;
    },
    removeFile() {
      this.file = '';
      this.fileIsValid = false;
    },
    importNewPI() {}
  },
  computed: {
    title() {
      return this.$t('Import Process');
    },
    subtitle() {
      return this.$t('Import a process from Workfellow PI (.jpg, .jpeg, .png) to this ProcessMaker environment');
    },
    buttons() {
      return [
        {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
        {'content': 'Import', 'action': 'importNewPI', 'variant': 'primary', 'disabled': this.importDisabled, 'hidden': false}
      ];
    }
  }
}
</script>