<template>
  <div id="importProcess" class="container mb-3" v-cloak>
    <div class="card p-4">
      <div class="text-left">
        <h4 class="pb-4 mb-0">{{ title() }}{{  name }}</h4>
        <p class="mb-0"><span class="fw-semibold">{{ boldSubtitle() }}</span>
          {{ subtitle() }}</p>
        <h4 class="py-4">Choose what to do with the assets:</h4>
        <ul class="asset-options list-unstyled">
          <li><span class="fw-semibold text-primary">Update:</span> {{ updateAsset() }}</li>
          <li><span class="fw-semibold text-primary">Keep Previous:</span> {{ keepAsset() }}</li>
          <li><span class="fw-semibold text-primary">Duplicate:</span> {{ duplicateAsset() }}</li>
        </ul>
      </div>
      <div>
        <template-asset-table :assets="templateAssets" @assetChanged="updateAssets"/>
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
      :templateName="name"
      @submitAssets="submitAssets"
    >
    </asset-loading-modal>
    <asset-confirmation-modal
      ref="assetConfirmationModal"
      :templateName="name"
      :submitResponse="submitResponse"
      :postComplete="postComplete"
      :processName="processName"
    >
    </asset-confirmation-modal>
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
  props: ['assets', 'name', 'responseId', 'request'],
  data() {
    return {
      templateAssets: [],
      templateName: "",
      updatedAssets: [],
      assetType: "update-assets",
      submitResponse: {},
      postComplete: false,
      processName: "",
    };
  },
  computed: {
  },
  watch: {
    assets() {
      this.templateAssets = this.assets;
    },
  },
  mounted() {
    this.templateAssets = this.assets;
    this.templateName = this.name;
  },
  methods: {
    reload() {
      window.location.reload();
    },
    onContinue() {
      this.$refs.assetLoadingModal.show();
    },
    submitAssets() {
      let formData = new FormData();
      formData.append("id", this.$root.responseId);
      formData.append("request", this.request);
      formData.append("existingAssets", JSON.stringify(this.updatedAssets));
      ProcessMaker.apiClient.post("/template/create/" + this.assetType + "/" + this.$root.responseId, formData)
        .then(response => {
          this.$nextTick(() => {
            this.$refs.assetLoadingModal.close();
          });
          this.processName = response.data.processName;
          this.submitResponse = response.data;
          this.postComplete = true;
          this.$refs.assetConfirmationModal.show();
        }).catch(error => {
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
      const formattedAssets = assets.reduce((accumulator, group) => {
        return accumulator.concat(group.items);
      }, []);

      this.updatedAssets = formattedAssets;
    },
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
