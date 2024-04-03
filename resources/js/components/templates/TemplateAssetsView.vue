<template>
  <div
    v-cloak
    id="importProcess"
    class="container mb-3"
  >
    <div class="card p-4">
      <div class="text-left">
        <h4 class="pb-4 mb-0">
          {{ title() }}{{ name }}
        </h4>
        <p class="mb-0">
          <span class="fw-semibold">{{ boldSubtitle() }}</span>
          {{ subtitle() }}
        </p>
        <h4 class="py-4">
          {{ $t("Choose what to do with the assets:") }}
        </h4>
        <ul class="asset-options list-unstyled">
          <li><span class="fw-semibold text-primary">{{ $t("Update:") }}</span> {{ updateAsset() }}</li>
          <li><span class="fw-semibold text-primary">{{ $t("Keep Previous:") }}</span> {{ keepAsset() }}</li>
          <li><span class="fw-semibold text-primary">{{ $t("Duplicate:") }}</span> {{ duplicateAsset() }}</li>
        </ul>
      </div>
      <div>
        <template-asset-table
          :assets="templateAssets"
          @assetChanged="updateAssets"
        />
      </div>
      <div
        class="card-footer bg-light text-right pr-0"
      >
        <button
          type="button"
          class="btn btn-primary"
          @click="onContinue"
        >
          {{ $t('Continue') }}
        </button>
      </div>
    </div>
    <asset-loading-modal
      ref="assetLoadingModal"
      :template-name="name"
      @submitAssets="submitAssets"
    />
    <asset-confirmation-modal
      ref="assetConfirmationModal"
      :template-name="name"
      :submit-response="submitResponse"
      :post-complete="postComplete"
      :process-name="processName"
      :redirect-to="redirectTo"
    />
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import AssetLoadingModal from "./AssetLoadingModal.vue";
import AssetConfirmationModal from "./AssetConfirmationModal.vue";
import TemplateAssetTable from "./TemplateAssetTable.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { TemplateAssetTable, AssetConfirmationModal, AssetLoadingModal },
  mixins: [uniqIdsMixin],
  props: {
    assets: {
      type: Array,
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
    responseId: {
      type: String,
      required: true,
    },
    request: {
      type: Object,
      required: true,
    },
    redirectTo: {
      type: [String, null],
      default: null,
    },
    wizardTemplateUuid: {
      type: String,
      required: false,
      default: null,
    },
  },
  data() {
    return {
      templateAssets: [],
      templateName: "",
      updatedAssets: [],
      assetType: "update-assets",
      submitResponse: {},
      postComplete: false,
      processName: "",
      importIsRunning: false,
    };
  },
  watch: {
    assets() {
      this.templateAssets = this.assets;
    },
  },
  mounted() {
    this.templateAssets = this.assets;
    this.templateName = this.name;

    this.$root.queue = window.ProcessMaker.queueImports;
    this.importIsRunning = window.ProcessMaker.importIsRunning;

    const userId = window.ProcessMaker.user.id;
    this.$root.log({type: 'init', message: 'Ready for import'});
    window.Echo.private(`ProcessMaker.Models.User.${userId}`).listen(
      '.ImportLog',
        (response) => {
            console.log("import log response handle on complete", response);
        //     this.$root.log({type: response.type, message: response.message});

        //     if (_.has(response, 'additionalParams.processId')) {
        //         this.handleOnComplete(response.additionalParams);
        //     }

        //     if (response.message === 'preview') {
        //         DataProvider.getImportManifest().then((manifestResponse) => {
        //             this.handleValidationResponse({
        //                 data: {
        //                     manifest: manifestResponse.data,
        //                     rootUuid: response.additionalParams.rootUuid,
        //                     processVersion: response.additionalParams.processVersion,
        //                 },
        //             });
        //         });
        //     }

        //     if (response.message === 'ProcessMaker\\Exception\\ImportPasswordException: password required') {
        //         this.showEnterPasswordModal();
        //     } else if (response.message === 'ProcessMaker\\Exception\\ImportPasswordException: incorrect password') {
        //         this.passwordError = "Incorrect password";
        //     } else if (response.type === 'error') {
        //         this.$root.allowDownloadDebug = true;
        //         ProcessMaker.alert(response.message, 'danger');
        //     }
        }
    );
  },
  methods: {
    reload() {
      window.location.reload();
    },
    onContinue() {
      this.$refs.assetLoadingModal.show();
    },
    submitAssets() {
      const formData = new FormData();
      formData.append("id", this.responseId);
      formData.append("request", JSON.stringify(this.request));
      formData.append("existingAssets", JSON.stringify(this.updatedAssets));
      formData.append("queue", this.$root.queue);
      if (this.wizardTemplateUuid !== null) {
        formData.append("wizardTemplateUuid", this.wizardTemplateUuid);
      }
      ProcessMaker.apiClient.post(`/template/create/${this.assetType}/${this.responseId}`, formData)
        .then((response) => {
          if (!this.$root.queue) {
            this.handleOnComplete(response);
          }
        }).catch((error) => {
          const message = error.response?.data?.error;
          ProcessMaker.alert(this.$t(message), "danger");
        });
    },
    title() {
      return this.$t("Use Template: ") + this.templateName;
    },
    boldSubtitle() {
      return this.$t("The process you've recently created includes assets that closely resemble those found in other existing processes.");
    },
    subtitle() {
      return this.$t("We advise against duplicating assets if you plan to use the same content as the existing ones.");
    },
    updateAsset() {
      return this.$t("Updates the already existing asset with a new blank version. Any previous customization of it will be erased.");
    },
    keepAsset() {
      return this.$t("The new process will be using the already existing asset.");
    },
    duplicateAsset() {
      return this.$t("A new blank asset will be created for the new process, without modifying the previously existing one.");
    },
    updateAssets(assets) {
      const formattedAssets = assets.reduce((accumulator, group) => accumulator.concat(group.items), []);

      this.updatedAssets = formattedAssets;
    },
    handleOnComplete(response) {
      this.$nextTick(() => {
        this.$refs.assetLoadingModal.close();
      });
      // Remove the state from local storage.
      localStorage.removeItem("templateAssetsState");

      this.processName = response.data.processName;
      this.submitResponse = response.data;
      this.postComplete = true;
      this.$refs.assetConfirmationModal.show();
    }
  },
};
</script>

<style type="text/css" scoped>
  [v-cloak] {
      display: none;
  }

  strong {
      font-weight: 700;
  }
</style>
