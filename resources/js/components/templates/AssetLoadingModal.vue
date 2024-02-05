<template>
  <div>
    <modal
      v-if="!loading"
      id="assetLoading"
      size="md"
      class="proceed-modal"
      :title="title"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      @onSubmit="onSubmit"
      @close="close"
    >
      <div>
        <p class="mt-1">Are you sure you want to proceed with your selection?</p>
      </div>
    </modal>
    <modal
      v-if="loading"
      id="assetLoading"
      size="md"
      :hide-header="true"
      :hide-footer="true"
    >
      <div class="text-center py-3">
        <span class="d-block mb-4"><h4>Applying Changes</h4></span>
        <span class="d-block mb-4"><p>Some assets can take some time to be ready</p></span>
        <b-spinner class="text-center" variant="primary" label="Loading..."></b-spinner>
      </div>
    </modal>
  </div>
</template>

<script>
import { Modal } from "SharedComponents";

export default {
  components: { Modal },
  props: ["templateName"],
  data: function() {
    return {
      loading: false,
      customModalButtons: [
        {"content": "Cancel", "action": "close", "variant": "outline-secondary"},
        {"content": "Yes", "action": "onSubmit", "variant": "primary"},
      ],
    }
  },
  computed: {
    title() {
      return this.$t("Confirmation");
    },
  },
  methods: {
    show() {
      this.$bvModal.show("assetLoading");
    },
    close() {
      this.$bvModal.hide("assetLoading");
    },
    onSubmit() {
      this.loading = true;
      this.$emit("submitAssets");
    },
  },
};
</script>

  <style>
    #assetLoading___BV_modal_footer_ {
      margin-top: 0 !important;
    }
  </style>
