<template>
    <div>
      <modal
        id="assetConfirmation"
        size="md"
        :title="title"
        :titleIcon="titleIcon"
        :setCustomButtons="true"
        :customButtons="customModalButtons"
        @handleRedirect="handleRedirect"
        @hidden="close"
      >
        <div>
          <p class="mt-1">
            The process <span class="font-weight-bold">{{ processName }}</span> was created successfully from the template <span class="font-weight-bold">{{ templateName }}</span>.
          </p>
        </div>

      </modal>
    </div>
  </template>
    
  <script>
  import { Modal } from "SharedComponents";
    
  export default {
    components: { Modal },
    props: ["templateName", "submitResponse", "processName", "assetRedirectionDestination", "destinationId"],
    data: function() {
      return {
        postComplete: false,
        customModalButtons: [
          {"content": "OK", "action": "handleRedirect", "variant": "primary", "size": "md"},
        ],
        titleIcon: "fas fa-check-circle text-success",
        processId: null,
      }
    },
    computed: {
      title() {
        return this.$t("Process Created Successfully");
      },
    },
    methods: {
      show() {
        this.$bvModal.show("assetConfirmation");
      },
      close() {
        this.$bvModal.hide("assetConfirmation");
      },
      handleRedirect() {
        if (this.assetRedirectionDestination === 'project' && this.destinationId) {
          window.location = `/designer/projects/${this.destinationId}`;
        } else {
          this.goToModeler();
        }
      },
      goToModeler() {
        this.processId = this.submitResponse.processId;
        window.location = "/modeler/" + this.processId;
      }
    },
  };
  </script>
  
  <style>
  
    #assetConfirmation___BV_modal_footer_ {
      margin-top: 0 !important;
    }
  
  </style>
  