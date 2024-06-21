<template>
  <div id="process-screen">
    <process-header
      :process="process"
      @goBack="goBack()"
      @onProcessNavigate="onProcessNavigate()"
      :enableCollapse="false"
    />
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
import ProcessHeader from "./ProcessHeader.vue";

export default {
  components: {
    DisplayScreen,
    ButtonsStart,
    EllipsisMenu,
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    LaunchpadSettingsModal,
    ProcessHeader,
  },
  mixins: [ellipsisMenuMixin, processNavigationMixin, ProcessesMixin],
  props: ["process", "currentUserId"],
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
          this.showScreen = response.data.config !== null;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("./scss/processes.css");
</style>
