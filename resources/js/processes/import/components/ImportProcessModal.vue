<template>
  <div>
    <modal 
      id="importProccess" 
      :title="title" 
      @update="onUpdate"
      @importNew="importNew"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      size="lg"
    >
      <template>
        <b-row align-v="start">
          <b-col :class="{'border-bottom': existingAssets.length}">
            <ul class="descriptions pl-3 ml-1">
              <li class="mb-1"><span class="fw-semibold">{{ $t('Import As New') }}</span>{{ willCreate() }}</li>
              <li v-if="userHasEditPermissions" class="mb-1"><span class="fw-semibold">{{$t('Update') }}</span>{{ willOverwrite() }}</li>
            </ul>
          </b-col>
        </b-row>
        <b-row align-v="start" class="pt-3">
          <b-col v-if="existingAssets.length" class="overflow-modal">
            <!-- Dashboard Warning -->
             <template v-if="hasDashboardRedirect">
            <warning-message
                :title="dashboardWarningTitle()"
                :helper-text="dashboardHelperText()"
              ></warning-message>
            </template>
            
            <!-- Asset Warnings -->
            <template v-for="(asset, index) in existingAssets">
              <warning-message
                :key="index"
                :title="warningTitle(asset)"
                :helper-text="helperText(asset)"
              ></warning-message>
            </template>

            <!-- No Update Permissions Message -->
            <p v-if="!userHasEditPermissions">{{ noUpdatePermissions(asset) }}</p>
          </b-col>
        </b-row>
        
      </template>
    </modal>
  </div>
</template>

<script>
  import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
  import Modal from "../../../components/shared/Modal";
  import WarningMessage from "./WarningMessage";
  
  export default {
    components: { Modal, WarningMessage },
    mixins: [ FormErrorsMixin ],
    props: ['existingAssets', 'processName','userHasEditPermissions'],
    data: function() {
      return {
        showModal: false,
        disabled: true,
        customModalButtons: [
            {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
            {'content': 'Update', 'action': 'update', 'variant': 'secondary', 'disabled': false, 'hidden': true},
            {'content': 'Import as New', 'action': 'importNew', 'variant': 'primary', 'disabled': false, 'hidden': false},
        ],
      }
    },
    computed: {
      title() {
        if (this.existingAssets.length > 0) {
          return this.$t('{{item}}', {type: this.existingAssets[0].typeHuman, item: this.processName});
        }
        return;
      },
      hasDashboardRedirect() {
        if (this.existingAssets.length > 0) {
          return this.existingAssets[0]?.references?.hasDashbordRedirect;
        }
      }
    },
    watch: {
    },
    beforeMount() {
        if (this.userHasEditPermissions) {
            this.customModalButtons[1].hidden = false;
        } else {
            this.customModalButtons[1].hidden = true;
        }
    },
    methods: {
      show() {
        this.$bvModal.show('importProccess');
      },
      close() {
        this.$bvModal.hide('importProccess');
      },
      onUpdate() {
        this.$emit('update-process');
        this.close();
      },
      importNew() {
        this.$emit('import-new');
        this.close();
      },
      willCreate() {
        if (this.existingAssets.length > 0) {
          return this.$t(" will create a new {{type}} in this environment.", { type: this.existingAssets[0].typeHuman.toLowerCase() });
        }
      },
      willOverwrite() {
        if (this.existingAssets.length > 0) {
          return this.$t(" will overwrite any assets tied to the current {{type}}. This may cause unintended side effects.", { type: this.existingAssets[0].typeHuman.toLowerCase() });
        }
      },
      dashboardWarningTitle(){
        return this.$t('Dashboard configuration was not imported');
      },
      warningTitle(asset) {
        return this.$t('{{type}} Already Exists', {type: asset.typeHuman});
      },
      dashboardHelperText() {
        return this.$t('Manually reconfigure the dashboard using an existing one in the environment.');
      },
      helperText(asset) {
        let text = "This environment already contains the {{ item }} named '{{ name }}.'";
        if (asset.existingUpdatedAt && asset.importingUpdatedAt) {
          const existingUpdatedAt = new Date(asset.existingUpdatedAt);
          const importingUpdatedAt = new Date(`${asset.importingUpdatedAt.replace(" ", "T")}.000000Z`);

          if (existingUpdatedAt > importingUpdatedAt) {
            text = "This environment already contains a newer version of the {{ item }} named '{{ name }}.'";
          } else if (existingUpdatedAt < importingUpdatedAt) {
            text = "This environment already contains an older version of the {{ item }} named '{{ name }}.'";
          } else {
            text = "This environment already contains the same version of the {{ item }} named '{{ name }}.'";
          }
        }
        
        text = this.$t(text, {
          item: asset.typeHuman.toLowerCase(),
          name: asset.importingName,
        });

        return text;
      },
      noUpdatePermissions(asset) {
          return this.$t("You do not have permissions to update the existing {{type}} in this environment.", { type: asset.typeHuman.toLowerCase() });
      },
    },
  };
</script>

<style scoped>
  .descriptions {
    list-style: inherit;
  }
  .overflow-modal {
    max-height: 30vh;
    overflow-y: auto;
  }
</style>
