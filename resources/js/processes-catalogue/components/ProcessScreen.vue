<template>
  <div id="process-screen">
    <process-header-start
        :process="process"
        :ellipsis-permission="ellipsisPermission"
        :show-process-info="showProcessInfo"
        @goBack="goBack()"
        @toggle-info="toggleInfo"
        @onProcessNavigate="onProcessNavigate"
        v-if="!mobileApp"
      />
    <display-screen
      v-if="showScreen"
      :screen="screen"
    />
    <slide-process-info
      :show="showProcessInfo"
      :title="title"
      :process="process"
      :full-carousel="fullCarousel"
      :is-wizard-template="createdFromWizardTemplate"
      @getHelperProcess="getHelperProcess"
      @closeCarousel="closeFullCarousel"
      @close="closeProcessInfo"
    >
      <div class="tw-flex tw-flex-col tw-gap-4 tw-pl-10 tw-pr-10">
        <carousel-slide
          :process="process"
          @full-carousel="showFullCarousel"
        />
        <div v-show="!fullCarousel">
          <process-options
            class="tw-w-full"
            :process="process"
            :collapsed="collapsed"
          />
        </div>
      </div>
    </slide-process-info>
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
import ProcessHeaderStart from "./ProcessHeaderStart.vue";
import SlideProcessInfo from "./slideProcessInfo/SlideProcessInfo.vue";
import CarouselSlide from "./CarouselSlide.vue";
import ProcessOptions from "./ProcessOptions.vue";
import WizardHelperProcessModal from "../../components/templates/WizardHelperProcessModal.vue";

const tceValidScreen = ["tce-student", "tce-college", "tce-grants"];

export default {
  components: {
    DisplayScreen,
    ButtonsStart,
    EllipsisMenu,
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    LaunchpadSettingsModal,
    ProcessHeaderStart,
    SlideProcessInfo,
    CarouselSlide,
    ProcessOptions,
    WizardHelperProcessModal,
  },
  mixins: [ellipsisMenuMixin, processNavigationMixin, ProcessesMixin],
  props: ["process", "currentUserId", "ellipsisPermission"],
  data() {
    return {
      screen: {},
      screen_id: "",
      showScreen: false,
      mobileApp: window.ProcessMaker.mobileApp,
      showProcessInfo: false,
      fullCarousel: false,
      collapsed: true,
    };
  },
  computed: {
    title() {
      return this.fullCarousel
        ? this.process.name
        : this.$t("Process Information");
    },
    createdFromWizardTemplate() {
      return !!this.process?.properties?.wizardTemplateUuid;
    },
    wizardTemplateUuid() {
      return this.process?.properties?.wizardTemplateUuid;
    },
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
        })
        .catch(() => {
          if(tceValidScreen.includes(this.screen_id)){
            window.ProcessMaker.alert(this.$t("TCE dashboards are currently unavailable, please contact with the administrator in order to enable"), "danger");
          }
        });
    },
    toggleInfo() {
      this.showProcessInfo = !this.showProcessInfo;
    },
    closeProcessInfo() {
      this.showProcessInfo = false;
    },
    showFullCarousel() {
      this.fullCarousel = true;
    },
    closeFullCarousel() {
      this.fullCarousel = false;
    },
    getHelperProcess() {
      if (this.$refs.wizardHelperProcessModal) {
        this.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("./scss/processes.css");
</style>
