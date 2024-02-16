<template>
    <div>
      <modal
        id="assetConfirmation"
        size="md"
        :title="title"
        :titleIcon="titleIcon"
        :setCustomButtons="true"
        :customButtons="customModalButtons"
        @goToModeler="goToModeler"
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
    props: ["templateName", "submitResponse", "processName", "redirectTo"],
    data: function() {
      return {
        postComplete: false,
        customModalButtons: [
          {"content": "OK", "action": "goToModeler", "variant": "primary", "size": "md"},
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
      goToModeler() {
        this.processId = this.submitResponse.processId;

        if (undefined !== this.redirectTo && null !== this.redirectTo) {
          if (this.redirectTo === 'process-launchpad') {
            window.location = `/process-browser/${this.processId}`;
          }
        } else  {
          window.location = "/modeler/" + this.processId;
        }
      }
    },
  };
  </script>

  <style>

    #assetConfirmation___BV_modal_footer_ {
      margin-top: 0 !important;
    }

  </style>
