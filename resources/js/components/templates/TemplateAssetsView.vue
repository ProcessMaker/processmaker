<template>
  <div id="importProcess" class="container mb-3" v-cloak>
    <div class="card p-4">
      <div class="text-left">
        <h4 class="pb-4 mb-0">{{ title() }}</h4>
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
        <template-asset-table />
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
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import TemplateAssetTable from "./TemplateAssetTable.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { TemplateAssetTable },
  filters: {
    titleCase: function (value) {
      value = value.toString();
      return value.charAt(0).toUpperCase() + value.slice(1);
    }
  },
  mixins: [uniqIdsMixin],
  props: ['assets'],
  data() {
    return {
      templateAssets: [],
    };
  },
  computed: {
  },
  watch: {
    assets() {
      console.log("ASSETS CHANGED", this.assets);
    }
  },
  mounted() {
    this.templateAssets = this.assets;
  },
  methods: {
    reload() {
      window.location.reload();
    },
    onCancel() {
      window.location = "/processes";
    },
    onContinue() {
      console.log('hit onContinue');
    },
    title() {
      return this.$t("Use Template: [[ Template Name ]]");
    },
    boldSubtitle() {
      return this.$t("The project you've recently created includes assets that closely resemble those found in other existing projects.");
    },
    subtitle() {
      return this.$t("We advise against duplicating assets if you plan to use the same content as the existing ones.");
    },
    updateAsset() {
      return this.$t("Updates the already existing asset with a new blank version. Any previous customization of it will be erased.");
    },
    keepAsset() {
      return this.$t("The new project will be using the already existing asset.");
    },
    duplicateAsset() {
      return this.$t("A new blank asset will be created for this project, without modifying the previously existing one.");
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

     .card-body {
         transition: all 1s;
     }

     .border-dotted {
         border: 3px dotted #e0e0e0;
     }

     .fw-medium {
         font-weight:500;
     }
 </style>
