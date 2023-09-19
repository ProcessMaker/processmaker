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
    <asset-confirmation-modal
      ref="assetConfirmationModal"
      :templateName="name"
      @submitAssets="submitAssets"
    >
    </asset-confirmation-modal>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import AssetConfirmationModal from "./AssetConfirmationModal.vue";
import TemplateAssetTable from "./TemplateAssetTable.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { TemplateAssetTable, AssetConfirmationModal },
  filters: {
    titleCase: function (value) {
      value = value.toString();
      return value.charAt(0).toUpperCase() + value.slice(1);
    }
  },
  mixins: [uniqIdsMixin],
  props: ['assets', 'name'],
  data() {
    return {
      templateAssets: [],
      templateName: "",
      updatedAssets: [],
    };
  },
  computed: {
  },
  watch: {
    assets() {
      console.log('HIT HERE');
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
    onCancel() {
      window.location = "/processes";
    },
    onContinue() {
      this.$refs.assetConfirmationModal.show();
    },
    submitAssets() {
      console.log('hit submitAssets');
      let formData = new FormData();
        console.log('UPDATED ASSETS', this.updatedAssets);
        // formData.append(this.templateAssets);
        // console.log('this.assetType', this.assetType);
        // console.log('this.assetId', this.assetId);
        // console.log('formData', formData);
        // ProcessMaker.apiClient.post("/template/create/" + this.assetType + "/" + this.assetId, formData)
        // .then(response => {
        //   ProcessMaker.alert(this.$t("PM Block successfully created"), "success");
        //   window.setTimeout(() => {
        //     window.location.href = `/designer/pm-blocks/${response.data.id}/edit/`;
        //   }, 1000);
        // }).catch(error => {
        //   this.errors = error.response?.data;
        //   this.customModalButtons[1].disabled = false;
        //   if (this.errors.hasOwnProperty('errors')) {
        //     this.errors = this.errors?.errors;
        //   } else {
        //     const message = error.response?.data?.error;
        //     ProcessMaker.alert(this.$t(message), "danger");
        //   }
        // });
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
      console.log('assets', assets);
      this.updatedAssets = assets;
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
