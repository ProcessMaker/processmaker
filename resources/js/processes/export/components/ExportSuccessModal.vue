<template>
  <div>
    <modal 
      id="exportSuccessModal" 
      :title="$t('Process Export Successful')" 
      :ok-title="$t('Close')"
      :ok-disabled="disabled"
      @ok.prevent="onClose"
      :ok-only="true"
    >
    <template>
        <div class="export-successful">
            <h6 class="card-title export-type">
              <span class="font-weight-bold">{{ processName }}</span>{{ $t(" was successfully exported.") }}
            </h6>
        </div>
        <div class="exported-assets pt-2">
            <h5 class="card-title export-type">{{ $t("Exported Assets") }}</h5>
        </div>
        <ul class="pl-0">
            <li v-for="(value, key) in exportedAssets" :key="value">
               <i class="fas fa-check-circle text-success"></i> {{ value }} {{ key }}
            </li>
        </ul>
        <template v-if="advancedExport">
            <div class="non-exported-assets">
                <h5 class="card-title export-type">{{ $t("Not Exported") }}</h5>
            </div>
            <ul class="pl-0">
                <li v-for="(value, key) in nonExportedAssets" :key="value">
                <i class="fas fa-minus-circle"></i> {{ value }} {{ key }}
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
  props: ["processId", "processName"],
  mixins: [],
  data() {
      return {
        disabled: false,
        advancedExport: true,
        exportedAssets: {
            screens: '23',
            signals: '5',
            dataConnectors: '3'
        },
        nonExportedAssets: {
            scripts: '18', 
            environmentVariables: '0',
            vocabularies: '1'
        }
      }
  },
  computed: {
  },

  watch: {
  },

  methods: { 
    onClose() {
        window.location = "/processes";
    }
  },  

  mounted() {
  }

}
</script>

<style>

  ul {
    list-style: none;
  }
</style>