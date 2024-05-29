<template>
  <div id="processCollapseInfo">
    <div id="processData">
      <div
        id="header"
        class="card card-body"
      >
        <div class="d-flex justify-content-between">
          <div class="d-flex align-items-center">
            <i
              class="fas fa-arrow-left text-secondary mr-2 iconTitle"
              @click="goBack"
            />
            <button
              class="btn border-0 header-process title-process-button"
              type="button"
              data-toggle="collapse"
              data-target="#collapseProcessInfo"
              aria-controls="collapseProcessInfo"
              :aria-expanded="infoCollapsed"
              @click="toogleInfoCollapsed()"
            >
              <template v-if="infoCollapsed">
                {{ $t('Process Info') }}
                <i class="fas fa-angle-up pl-2" />
              </template>
              <template v-else>
                {{ getNameEllipsis() }}
                <i class="fas fa-angle-down pl-2" />
              </template>
            </button>
          </div>
          <div class="d-flex align-items-center">
            <div class="card-bookmark mx-2">
              <i
                :ref="`bookmark-${process.id}-marked`"
                v-b-tooltip.hover.bottom
                :title="$t(labelTooltip)"
                class="fas fa-bookmark"
                :class="{ marked: showBookmarkIcon, unmarked: !showBookmarkIcon }"
                @click="checkBookmark(process)"
              />
            </div>
            <span class="ellipsis-border">
              <ellipsis-menu
                v-if="showEllipsis"
                :actions="processLaunchpadActions"
                :permission="permission"
                :data="process"
                :is-documenter-installed="isDocumenterInstalled"
                :divider="false"
                :lauchpad="true"
                variant="none"
                @navigate="onProcessNavigate"
              />
            </span>
            <buttons-start :process="process" />
          </div>
        </div>
      </div>
      <div
        id="collapseProcessInfo"
        class="collapse show"
      >
        <div class="info-collapse">
          <div class="row">
            <div class="wizard-container col-sm-3">
              <div class="wizard">
                <b-button
                  v-if="createdFromWizardTemplate"
                  class="mt-2 wizard-link"
                  variant="link"
                  @click="getHelperProcess"
                >
                  <img
                    src="../../../img/wizard-icon.svg"
                    :alt="$t('Guided Template Icon')"
                  >
                  {{ $t('Re-run Wizard') }}
                </b-button>
              </div>
            </div>
          </div>
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
    
    <!-- <div w-90 h-90 v-show="false">
      <div class="card card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex align-items-center">
          <i class="fas fa-angle-left"
          @click="goBack"/>
          <span style="margin-left: 10px;">Process Name 1 of 4</span>
        </div>
      </div>
      </div>
      <processes-carousel
        :process="process"
        :full-carousel="true"
      />
    </div> -->
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
    <wizard-helper-process-modal
      v-if="createdFromWizardTemplate"
      id="wizardHelperProcessModal"
      ref="wizardHelperProcessModal"
      :process-launchpad-id="process.id"
      :wizard-template-uuid="wizardTemplateUuid"
    />
  </div>
</template>

<script>
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import LaunchpadSettingsModal from "../../components/shared/LaunchpadSettingsModal.vue";
import WizardHelperProcessModal from "../../components/templates/WizardHelperProcessModal.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";
import ProcessOptions from "./ProcessOptions.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import ProcessesMixin from "./mixins/ProcessesMixin";

export default {
  components: {
    ButtonsStart,
    EllipsisMenu,
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    LaunchpadSettingsModal,
    ProcessOptions,
    ProcessesCarousel,
    WizardHelperProcessModal,
  },
  mixins: [ProcessesMixin, ellipsisMenuMixin, processNavigationMixin],
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId"],
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
  methods: {
    /**
     * Change button title
     */
    toogleInfoCollapsed() {
      this.infoCollapsed = !this.infoCollapsed;
    },
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
      this.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("./scss/processes.css");
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
.prev,
.next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background-color: #333;
  color: #fff;
  border: none;
  padding: 10px;
  cursor: pointer;
}
.prev {
  left: 0;
}
.next {
  right: 0;
}
</style>
