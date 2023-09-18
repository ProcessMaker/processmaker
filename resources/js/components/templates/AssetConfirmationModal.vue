<template>
  <div>
    <modal
      v-if="!loading"
      id="assetConfirmation"
      size="md"
      class="proceed-modal"
      :title="title"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      @onSubmit="onSubmit"
      @hidden="close"
    >
      <div>
        <p class="mt-1">Are you sure you want to proceed with your selection?</p>
      </div>
    </modal>
    <modal
      v-if="loading"
      id="assetConfirmation"
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
    <modal
      v-if="success"
      id="assetConfirmation"
      size="md"
      :title="title"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      @ok.prevent="onSubmit"
    >
      <div>
        <p class="mt-1">The process [[Name of Process]] was created successfully from the template [[ Name of Template]].</p>
      </div>
    </modal>
  </div>
</template>
  
<script>
import { Modal } from "SharedComponents";
  
export default {
  components: { Modal },
  props: [],
  data: function() {
    return {
      loading: false,
      success: true,
      customModalButtons: [
        {"content": "Cancel", "action": "close", "variant": "outline-secondary", "disabled": false, "hidden": false},
        {"content": "Yes", "action": "onSubmit", "variant": "primary", "disabled": false, "hidden": false},
      ],
    }
  },
  computed: {
    title() {
      if (!this.loading) {
        return this.$t("Confirmation");
      }

      if (this.success) {
        return this.$t("Process Created Successfully");
      }

      return;
    },
  },
  methods: {
    show() {
      this.$bvModal.show("assetConfirmation");
    },
    close() {
      this.$bvModal.hide("assetConfirmation");
    },
    onSubmit() {
      //post to backend
      //this.loading = true
      //when done, this.loading = false, this.success = true;
      console.log("submit");
    },
  },
  mounted() {
  }
};
</script>

<style>

  #assetConfirmation___BV_modal_footer_ {
    margin-top: 0 !important;
  }

</style>
