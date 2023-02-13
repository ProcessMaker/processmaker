<template>
  <div>
    <modal 
      id="exportSuccessModal" 
      :title="$t('Process Export Successful')" 
      :ok-title="$t('Close')"
      :ok-disabled="disabled"
      @ok.prevent="onClose"
      :ok-only="true"
      @close="onClose"
    >
    <template>
        <div class="export-successful">
            <h6 class="card-title export-type">
              <span class="font-weight-bold text-capitalize">{{ processName }}</span>{{ $t(" was successfully exported.") }}
            </h6>
        </div>
        <div class="exported-assets pt-2">
            <h5 class="card-title export-type">{{ $t("Exported Assets") }}</h5>
        </div>
        <ul class="pl-0">
            <li v-for="(value, key) in this.exportInfo.exported" :key="key">
               <i class="fas fa-check-circle text-success"></i>{{formatAssetValue(value) }} {{ formatAssetName(key) }}
            </li>
        </ul>
        <template v-if="this.$root.includeAll === false">
            <div class="non-exported-assets">
                <h5 class="card-title export-type">{{ $t("Not Exported") }}</h5>
            </div>
            <ul class="pl-0">
                <li v-for="(key, value) in filterNonExported" :key="value">
                <i class="fas fa-minus-circle"></i> {{ key.items.length }} {{ key.typeHuman }}
                </li>
            </ul>
        </template>
    </template>
    </modal>
  </div>
</template>

<script>
import { Modal } from "SharedComponents";

export default {
  components: { Modal },
  props: ["processId", "processName", "exportInfo", "info"],
  mixins: [],
  data() {
      return {
        disabled: false,
        nonExportedAssets: {},
      }
  },
  computed: {
    filterNonExported() {
      const nonExportedAssets = Object.entries(this.$root.includeAllByGroup).filter(([key, value]) => !value);
      const filterNonExported = nonExportedAssets.map((item) => item[0]);
      const result = this.info.filter(item => filterNonExported.includes(item.type));
      return result;
    },
  },
  watch: {
    exportInfo() {
        if (!this.exportInfo) {
            return;
        }
    }
  },

  methods: { 
    onClose() {
        window.location = "/processes";
    },
    show() {
      this.$bvModal.show('exportSuccessModal');
    },
    hide() {
      this.$bvModal.hide('exportSuccessModal');
    },
    formatAssetName(string) {
        let newString = string.replaceAll('_', ' ');
        let assetString = newString.split(' ');
        for (let i = 0; i < assetString.length; i++) {
            assetString[i] = assetString[i].charAt(0).toUpperCase() + assetString[i].slice(1);
        }
        return assetString.join(' ');
    },
    formatAssetValue(value) {
        return value.length;
    },
  },
}
</script>

<style>

  ul {
    list-style: none;
  }

  i {
    padding-right: 5px;
  }
</style>