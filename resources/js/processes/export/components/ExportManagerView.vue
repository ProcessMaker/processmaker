<template>
  <div class="container" id="exportProcess">
    <div class="row">
      <div class="col">
        <div class="card text-center">
          <div class="card-header bg-light" align="left">
            <h5>{{ $t("Export Process") }}</h5>
            <h6 class="text-muted">{{ $t("Download a process model and its associated assets.") }}</h6>
          </div>
          <div class="card-body" align="left">
            <h5 class="card-title export-type">{{ $t("You are about to export") }}
              <span class="font-weight-bold">{{ processName + "."}}</span>
            </h5>
            <div>
              <b-form-group label="Select Export Type" class="medium-font">
                <b-form-radio
                    v-for="(item, index) in exportTypeOptions"
                    v-model="selectedExportOption"
                    :aria-describedby="index.toString()"
                    :key="item.value"
                    :value="item.value"
                >
                    <span class="fw-medium">{{ item.content }}</span>
                    <div>
                        <small class="text-muted">{{ item.helper }}</small>
                    </div>
                </b-form-radio>
            </b-form-group>
            <div v-if="!$root.canExport">
              <div class="form-group medium-font mt-3 text-center">
                <i class="fas fa-spin fa-spinner p-0 mr-2"></i>{{ $t("Retrieving manifest for export please wait...")}}
              </div>
            </div>
          </div>
          </div>
          <div class="card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary" @click="onCancel">
              {{ $t("Cancel") }}
            </button>
            <button :disabled="!$root.canExport" type="button" class="btn btn-primary ml-2" @click="onExport">
              {{ $t("Export") }}
            </button>
            <set-password-modal ref="set-password-modal" :processId="processId" :processName="processName" @verifyPassword="exportProcess" :ask="true" />
            <export-success-modal ref="export-success-modal" :processName="processName" :processId="processId" :exportInfo="exportInfo" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import SetPasswordModal from "./SetPasswordModal.vue";
import ExportSuccessModal from "./ExportSuccessModal.vue";
import DataProvider from "../DataProvider";

export default {
  components: {
    SetPasswordModal,
    ExportSuccessModal,
  },
  mixins: [],
  props: ["processId", "processName"],
  data() {
    return {
      exportTypeOptions: [
        {"value": "basic", "content": "Basic", "helper": "Download all related assets."},
        {"value": "custom", "content": "Custom", "helper": "Select which assets to include in the export file for a custom export package."},
      ],
      selectedExportOption: "basic",
      exportInfo: {},
      // processInfo: {},
    };
  },
  mounted() {
    this.$root.getManifest(this.processId);
  },
  methods: {
    onCancel() {
      window.location = "/processes";
    },
    showSetPasswordModal() {
      this.$refs["set-password-modal"].show();
    },
    onExport() {
      switch (this.selectedExportOption) {
        case "basic":
          this.showSetPasswordModal();
          break;
        case "custom":
          this.handleCustomExport();
          break;
        default:
          this.showSetPasswordModal();
          break;
      }
    },
    exportProcess(password) {
      DataProvider.exportProcess(this.processId, password, [])
        .then((exportInfo) => {
            this.exportInfo = exportInfo;
            this.$refs['export-success-modal'].show();
            this.$refs['set-password-modal'].hide();
        })
        .catch((error) => {
            ProcessMaker.alert(error, "danger");
        });
    },
    handleCustomExport() {
      this.$router.push({ name: "export-custom-process" });
    },
  }
}   
</script>
    
<style lang="scss" scoped>
.medium-font {
  font-weight: 500;
}
</style>
