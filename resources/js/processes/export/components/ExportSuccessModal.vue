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
            <li v-for="(value, key) in exportInfo.exported" :key="key">
               <i class="fas fa-check-circle text-success"></i>{{ value.ids.length }}
               <span v-if="value.ids.length > 1">{{ value.name_plural }}</span>
               <span v-else>{{ value.name }}</span>
            </li>
        </ul>
        <template v-if="$root.includeAll === false">
            <div class="non-exported-assets">
                <h5 class="card-title export-type">{{ $t("Not Exported") }}</h5>
            </div>
            <ul class="pl-0">
                <li v-for="(value, key) in filterNonExported" :key="key">
                <i class="fas fa-minus-circle"></i>{{ value.ids.length }}
                <span v-if="value.ids.length > 1">{{ value.firstAsset.type_human_plural }}</span>
                <span v-else>{{ value.firstAsset.type_human }}</span>
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
      }
  },
  computed: {
    filterNonExported() {
      return Object.entries(this.$root.ioState).filter(([uuid, setting]) => {
        return setting.mode === 'discard';
      }).map(([uuid, setting]) => {
        return this.$root.manifest[uuid];
      }).reduce((groups, asset) => {
        if(!(asset.type in groups)) {
          groups[asset.type] = {
            firstAsset: asset,
            ids: [],
          };
        }
        groups[asset.type].ids.push(asset.attributes.id);
        return groups;
      }, {});
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