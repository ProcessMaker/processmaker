<template>
  <div class="container" id="exportProcess">
    <div class="row">
      <div class="col">
        <div class="card text-center">
          <div class="card-header bg-light" align="left">
            <h5>{{ $t("Export Process") }}</h5>
            <h6 class="text-muted">
              {{ $t("Download a process model and its associated assets.") }}
            </h6>
          </div>
          <div class="card-body" align="left">
            <h5 class="card-title export-type">
              {{ $t("You are about to export") }}
              <span class="font-weight-bold">{{
                $t("[[Process Name]].")
              }}</span>
            </h5>
            <div>
              <b-form-group label="Select Export Type" class="medium-font">
                <div class="export-type">
                  <b-form-radio
                    v-model="selected"
                    aria-describedby="basic-export-type"
                    name="basic-export-option"
                    value="basic"
                  >
                    {{ $t("Basic") }}
                  </b-form-radio>
                  <b-form-text id="basic-export-type" class="form-text">
                    {{ $t("Download all related assets.") }}
                  </b-form-text>
                </div>
                <div class="export-type">
                  <b-form-radio
                    v-model="selected"
                    aria-describedby="custom-export-type"
                    name="custom-export-option"
                    value="custom"
                  >
                    {{ $t("Custom") }}
                  </b-form-radio>
                  <b-form-text id="custom-export-type" class="form-text">
                    {{
                      $t(
                        "Select which assets to include in the export file for a custom export package."
                      )
                    }}
                  </b-form-text>
                </div>
              </b-form-group>
            </div>
          </div>
          <div class="card-footer bg-light" align="right">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="onCancel"
            >
              {{ $t("Cancel") }}
            </button>
            <button
              type="button"
              class="btn btn-primary ml-2"
              @click="onExport"
            >
              {{ $t("Export") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["processId"],
  components: {},
  mixins: [],
  data() {
    return {
      selected: "",
    };
  },
  methods: {
    onCancel() {
      window.location = "/processes";
    },
    onExport() {
      ProcessMaker.apiClient
        .post("processes/" + this.processId + "/export")
        .then((response) => {
          window.location = response.data.url;
          ProcessMaker.alert(this.$t("The process was exported."), "success");
        })
        .catch((error) => {
          ProcessMaker.alert(error.response.data.message, "danger");
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.export-type {
  padding-bottom: 10px;
}
.form-text {
  text-indent: 2em;
  margin-top: -5px;
}
.medium-font {
  font-weight: 500;
}
</style>