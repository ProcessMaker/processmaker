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
              </b-form-group>
            </div>
          </div>
          <div class="card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary" @click="onCancel">
              {{ $t("Cancel") }}
            </button>
            <button type="button" class="btn btn-primary ml-2" v-b-modal.set-password-modal>
              {{ $t("Export") }}
            </button>
            <set-password-modal :processId="processId"></set-password-modal>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import SetPasswordModal from './SetPasswordModal.vue';

export default {
  props: ["processId", 'processName'],
  components: {
    SetPasswordModal
  },
  mixins: [],
  data() {
    return {
      selected: "basic"
    };
  },
  methods: {
    onCancel() {
      window.location = "/processes";
    },
    showSetPasswordModal() {
        this.$bvModal.show('setPasswordModal');
    }
  },
};
</script>

<style lang="scss" scoped>
.medium-font {
  font-weight: 500;
}
</style>