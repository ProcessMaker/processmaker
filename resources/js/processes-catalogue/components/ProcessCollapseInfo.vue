<template>
  <div id="processCollapseInfo">
    <div id="processData">
      <process-header-start
        :process="process"
        @goBack="goBack()"
      />
      <process-header
        :process="process"
        :hide-header-options="true"
        :icon-wizard-template="createdFromWizardTemplate"
        @goBack="goBack()"
        @onProcessNavigate="onProcessNavigate"
      />
      <div
        id="collapseProcessInfo"
        class="collapse show custom-class"
      >
        <div class="info-collapse">
          <b-row>
            <b-col class="process-carousel col-12">
              <processes-carousel
                :process="process"
                :full-carousel="{ url: null, hideLaunchpad: false }"
              />
            </b-col>
            <b-col class="process-options col-12">
              <process-options :process="process" />
            </b-col>
          </b-row>
        </div>
      </div>
    </div>
    
    <create-template-modal
      id="create-template-modal"
      ref="create-template-modal"
      asset-type="process"
      :current-user-id="currentUserId"
      :asset-name="processTemplateName"
      :asset-id="processId"
    />
    <create-pm-block-modal
      id="create-pm-block-modal"
      ref="create-pm-block-modal"
      :current-user-id="currentUserId"
      :asset-name="pmBlockName"
      :asset-id="processId"
    />
    <add-to-project-modal
      id="add-to-project-modal"
      ref="add-to-project-modal"
      asset-type="process"
      :asset-id="processId"
      :asset-name="assetName"
    />
    <launchpad-settings-modal
      id="launchpad-settings-modal"
      ref="launchpad-settings-modal"
      asset-type="process"
      origin="core"
      :options="optionsData"
      :description-settings="process.description"
      :process="process"
    />
  </div>
</template>

<script>
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import LaunchpadSettingsModal from "../../components/shared/LaunchpadSettingsModal.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";
import ProcessOptions from "./ProcessOptions.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import ProcessesMixin from "./mixins/ProcessesMixin";
import ProcessHeader from "./ProcessHeader.vue";
import ProcessHeaderStart from "./ProcessHeaderStart.vue";

export default {
  components: {
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    LaunchpadSettingsModal,
    ProcessOptions,
    ProcessesCarousel,
    ProcessHeader,
    ProcessHeaderStart,
  },
  mixins: [ProcessesMixin, ellipsisMenuMixin, processNavigationMixin],
  props: ["process", "currentUserId"],
  computed: {
    createdFromWizardTemplate() {
      return !!this.process?.properties?.wizardTemplateUuid;
    },
    wizardTemplateUuid() {
      return this.process?.properties?.wizardTemplateUuid;
    },
  },
  mounted() {
    this.verifyDescription();
    ProcessMaker.EventBus.$on("reloadByNewScreen", (newScreen) => {
      window.location.reload();
    });
  },
  data() {
    return {
    };
  },
  methods: {
    /**
     * Verify if the Description is large
     */
    verifyDescription() {
      if (this.process.description.length > 200) {
        this.largeDescription = true;
      }
    },
    /**
     * Show the whole large description
     */
    activateReadMore() {
      this.readActivated = true;
    },
    getHelperProcess() {
      console.log("en getHelperProcess: ", this.$refs.wizardHelperProcessModal);
      this.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("./scss/processes.css");
@import '~styles/variables';

#collapseProcessInfo {
  @media (max-width: $lp-breakpoint) {
    display: none;
  }
}
.custom-class {
 margin-top: -13px;
}
.wizard-link {
  text-transform: none;
}
.wizard-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.wizard {
  display: flex;
  justify-items: end;
  width: 294px;
}
@media (width < 1200px) {
  .process-options {
    margin-top: 32px;
  }
  .wizard {
    width: 170px;
  }
}
@media (1460px <= width < 1600px) {
  .col-pm-9 {
    flex: 0 0 70%;
    max-width: 70%;
  }
  .col-pm-3 {
    flex: 0 0 30%;
    max-width: 30%;
  }
}
@media (1367px <= width < 1460px) {
  .col-pm-9 {
    flex: 0 0 70%;
    max-width: 70%;
  }
  .col-pm-3 {
    flex: 0 0 30%;
    max-width: 30%;
  }
}
@media (1200 <= width <= 1366) {
  .process-options {
    margin-top: 32px;
  }
  .col-pm-9 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  .col-pm-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}

.header {
  @media (max-width: $lp-breakpoint) {
    display: none;
  }
}

.header-mobile {
  display: none;
  padding: 1em;

  .title {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 1.5em;
  }

  @media (max-width: $lp-breakpoint) {
    display: flex;
    flex-direction: row;
    align-items: center;
  }
}
</style>
