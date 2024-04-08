<template>
  <div id="process-screen">
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
          {{ process.name }}
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
    <display-screen
      v-if="showScreen"
      :screen="screen"
    />
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
import DisplayScreen from "./utils/DisplayScreen.vue";
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import LaunchpadSettingsModal from "../../components/shared/LaunchpadSettingsModal.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import ProcessesMixin from "./mixins/ProcessesMixin";

export default {
  components: {
    DisplayScreen,
    ButtonsStart,
    EllipsisMenu,
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    LaunchpadSettingsModal,
  },
  mixins: [ellipsisMenuMixin, processNavigationMixin, ProcessesMixin],
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId"],
  data() {
    return {
      screen: {},
      screen_id: "",
      showScreen: false,
    };
  },
  mounted() {
    this.getScreen();
    ProcessMaker.EventBus.$on("reloadByNewScreen", (newScreen) => {
      window.location.reload();
    });
  },
  methods: {
    /**
     * Get the screen for the process in Launchpad
     */
    getScreen() {
      this.screen_id = JSON.parse(this.process.launchpad.properties).screen_id;
      ProcessMaker.apiClient
        .get(`screens/${this.screen_id}`)
        .then((response) => {
          this.screen = response.data;
          this.showScreen = true;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("./scss/processes.css");
</style>
