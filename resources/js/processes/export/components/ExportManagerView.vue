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
              <!-- <b-form-group label="Select Export Type" class="medium-font">
                <div class="pb-1">
                  <b-form-radio v-model="selected" aria-describedby="basic-export-type" name="basic-export-option" value="basic">
                    {{ $t("Basic") }}
                    <div class="helper-text text-muted">
                        <small id="basic-export-type">{{ $t("Download all related assets.") }}</small>
                    </div>
                  </b-form-radio>
                </div>
                <div class="pb-1">
                  <b-form-radio v-model="selected" aria-describedby="custom-export-type" name="custom-export-option" value="custom">
                    {{ $t("Custom") }}
                    <div class="helper-text text-muted">
                        <small id="custom-export-type">{{ $t("Select which assets to include in the export file for a custom export package.") }}</small>
                    </div>
                  </b-form-radio>
                </div>
              </b-form-group> -->
            </div>
          </div>
          <div class="card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary" @click="onCancel">
              {{ $t("Cancel") }}
            </button>
            <button type="button" class="btn btn-primary ml-2" @click="showSetPasswordModal">
              {{ $t("Export") }}
            </button>
            <set-password-modal ref="set-password-modal" :processId="processId" :processName="processName" @verifyPassword="exportProcess" />
            <export-success-modal ref="export-success-modal" :processName="processName" :processId="processId" :exportInfo="exportInfo" />
            <custom-export-view ref="custom-export-view" :processName="processName" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import SetPasswordModal from "./SetPasswordModal.vue";
import ExportSuccessModal from "./ExportSuccessModal.vue";
import CustomExportView from "./CustomExportView.vue";

export default {
  components: {
    SetPasswordModal,
    ExportSuccessModal,
    CustomExportView,
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
    };
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
    exportProcess() {
        const params = {
            password: null,
            options: [],
        };
        ProcessMaker.apiClient({
            url: `export/process/download/${this.processId}`,
            data: params,
            method: "POST",
            responseType: "blob",
        })
        .then((response) => {
            let header = response.headers['export-info'];
            this.exportInfo = JSON.parse(header);
            this.$refs['export-success-modal'].show();
            this.$refs['set-password-modal'].hide();
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", this.processName + ".json");
            document.body.appendChild(link);
            link.click();
        })
        .catch((error) => {
            ProcessMaker.alert(error.response.data.message, "danger");
        });
    },
    handleCustomExport() {
      // this.exportProcess();
    },
  }
}   
</script>
    
<style lang="scss" scoped>
.medium-font {
  font-weight: 500;
}
</style>