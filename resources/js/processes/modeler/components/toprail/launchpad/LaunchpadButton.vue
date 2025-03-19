<template>
  <div v-if="!isTemplate">
    <button
      v-b-tooltip.hover.viewport.d50="{ customClass: 'no-pointer-events' }"
      type="button"
      class="btn btn-white"
      :title="$t('Open Launchpad')"
      data-cy="launchpad-button"
      @mouseleave="handleMouseLeave"
      @mouseover="handleMouseOver"
      @click.prevent="handleOpenLaunchpad"
    >
      <i :class="iconOpen" />
    </button>
    <launchpad-modal
      :show="showLaunchpadModal"
      @closeModal="closeModal"
    />
  </div>
</template>

<script>
import LaunchpadModal from "./LaunchpadModal.vue";

export default {
  components: {
    LaunchpadModal,
  },
  props: {
    isTemplate: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      iconOpen: "fas fa-play",
      showLaunchpadModal: false,
      openLaunchpad: false,
    };
  },
  methods: {
    handleMouseOver() {
      this.iconOpen = "fas fa-external-link-alt";
    },
    handleMouseLeave() {
      this.iconOpen = "fas fa-play";
    },
    handleOpenLaunchpad() {
      this.openLaunchpad = window.ProcessMaker.modeler.launchpad === null;
      if (this.openLaunchpad) {
        this.showLaunchpadModal = true;
      } else {
        this.openLaunchpad = false;
        window.location.href = `/process-browser/${window.ProcessMaker.modeler.process.id}`;
      }
    },
    closeModal() {
      this.showLaunchpadModal = false;
    },
  },
};
</script>
