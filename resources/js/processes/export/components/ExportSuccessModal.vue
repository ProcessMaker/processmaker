<template>
  <div>
    <modal 
      id="exportSuccessModal" 
      :title="$t('Process Export Successful')" 
      :ok-title="$t('Close')"
      :ok-disabled="disabled"
      @ok.prevent="onClose"
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
        <template v-if="advancedExport">
            <div class="non-exported-assets">
                <h5 class="card-title export-type">{{ $t("Not Exported") }}</h5>
            </div>
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
        advancedExport: false
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
  .form-control {
    border-right: 0;
  }

  .form-btn {
    border: 1px solid #b6bfc6;
    border-left:none;
  }

  .disabled-form {
    border-right: 1px solid #b6bfc6;
  }

  .invalid {
    border-color: #E50130;
  }
</style>