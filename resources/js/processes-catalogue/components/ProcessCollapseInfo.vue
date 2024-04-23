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
          <div class="d-flex justify-content-between">
            <div class="process-description">
              <p class="title-process">
                {{ process.name }}
              </p>
              <p
                v-if="readActivated || !largeDescription"
                class="description"
              >
                {{ process.description }}
              </p>
              <p
                v-if="!readActivated && largeDescription"
                class="description"
              >
                {{ process.description.slice(0,300) }}
                <a
                  v-if="!readActivated"
                  class="read-more"
                  @click="activateReadMore"
                >
                  ...
                </a>
              </p>
            </div>
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
          <b-row>
            <b-col class="process-carousel col-sm-12 col-md-12 col-lg-12 col-xl-9 col-9">
              <processes-carousel
                :process="process"
              />
            </b-col>
            <b-col class="process-options col-sm-12 col-md-12 col-lg-12 col-xl-3 col-2">
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
@media (width < 1200px) {
  .process-options {
    margin-top: 32px;
  }
}
</style>
