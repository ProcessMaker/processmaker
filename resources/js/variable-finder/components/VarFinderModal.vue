<template>
  <div>
    <modal
      id="varFinder"
      class="var-finder-modal"
      :ok-disabled="disabled"
      :hide-footer="true"
      :hide-header="true"
      @hidden="onClose"
      @ok.prevent="onSubmit"
    >
      <div class="d-flex justify-content-between">
        <h6 class="pt-2">
          {{ $t("VarFinder") }}
        </h6>
        <button
          class="modal-close-btn"
          data-cy="close-var-finder"
          @click="close()"
        >
          <i class="fas fa-times" />
        </button>
      </div>
      <div class="d-flex panel-tabs justify-content-center">
        <b-button
          class="d-inline panel-tab-btn px-1"
          :class="{ 'recent-selected': recentSelected }"
          data-cy="recent-tab"
          @click="showRecentView"
        >
          {{ $t("Recent") }}
        </b-button>
        <b-button
          class="d-inline panel-tab-btn"
          :class="{ 'search-selected': searchSelected }"
          data-cy="search-tab"
          @click="showSearchView"
        >
          {{ $t("Search") }}
        </b-button>
      </div>
      <div class="d-flex justify-content-center">
        <div
          v-if="recentSelected"
          class="d-flex justify-content-center p-0"
          data-cy="recent-list"
        >
          <span
            v-if="loading"
            class="d-flex justify-content-center"
          >
            <b-spinner
              variant="primary"
              label="Spinning"
            />
          </span>
          <span
            v-if="noRecentAssets"
            class="p-2 h-100 overflow-auto"
          ><h5>{{ $t("No assets found.") }}</h5>
          </span>
          <latest-assets v-else />
        </div>
        <div
          v-if="searchSelected"
          class="d-flex justify-content-center p-0"
          data-cy="search-list"
        >
          <span
            v-if="loading"
            class="d-flex justify-content-center"
          >
            <b-spinner
              variant="primary"
              label="Spinning"
            />
          </span>
          <span
            v-if="noSearchResults"
            class="p-2 h-100 overflow-auto"
          >
            <h5>{{ $t("No templates found.") }}</h5>
          </span>
          <screen-template-card
            v-for="template in sharedTemplatesData"
            v-else
            :key="template.id"
            :template="template"
            :screen-id="screenId"
            :current-screen-page="currentScreenPage"
            :active-template-id="activeTemplateId"
            @toggle-active="setActiveTemplate"
          />
        </div>
      </div>
    </modal>
  </div>
</template>

<script>
import Modal from "../../components/shared/Modal.vue";
import LatestAssets from "./LatestAssets.vue";

export default {
  components: {
    Modal,
    LatestAssets,
  },
  mixins: [],
  props: [],
  data() {
    return {
      shortcutKeys: ["Meta", "Shift", "v"],
      pressedKeys: new Set(),
      recentSelected: true,
      searchSelected: false,
      isDesignerPage: false,
      disabled: false,
      loading: false,
      noRecentAssets: false,
      noSearchResults: false,
    };
  },
  computed: {},
  watch: {
    $route: {
      immediate: true,
      handler() {
        this.isDesignerPage = window.location.pathname.includes("/designer");
        console.log("isDesignerPage", this.isDesignerPage, window.location.pathname);
        if (this.isDesignerPage) {
          this.setupKeyboardListeners();
        } else {
          this.removeKeyboardListeners();
        }
      },
    },
  },
  mounted() {
    console.log("HIT MOUNTED");
    this.showRecentView();
  },
  beforeDestroy() {
    this.removeKeyboardListeners();
  },
  methods: {
    show() {
      console.log("SHOW MODAL");
      this.$bvModal.show("varFinder");
    },
    onClose() {
      //   this.resetFormData();
      //   this.resetErrors();
    },
    close() {
      this.$bvModal.hide("varFinder");
      this.onClose();
    },
    setupKeyboardListeners() {
      window.addEventListener("keydown", this.handleKeyDown);
      window.addEventListener("keyup", this.handleKeyUp);
    },
    removeKeyboardListeners() {
      window.removeEventListener("keydown", this.handleKeyDown);
      window.removeEventListener("keyup", this.handleKeyUp);
    },
    handleKeyDown(event) {
      console.log("handleKeyDown", event);
      this.pressedKeys.add(event.key);

      // Check if all shortcut keys are pressed
      if (this.shortcutKeys.every((key) => this.pressedKeys.has(key))) {
        this.show();
        event.preventDefault();
      }
    },
    handleKeyUp(event) {
      console.log("handleKeyUp", event);
      this.pressedKeys.delete(event.key);
    },
    showRecentView() {
      this.recentSelected = true;
      this.searchSelected = false;
    },
    showSearchView() {
      this.recentSelected = false;
      this.searchSelected = true;
    },
  },
};
</script>

<style lang="scss" scoped>
.var-finder-modal {
  border-radius:  16px;
  width: 576px;
  height: 812px;
}

.modal-close-btn {
  background-color: transparent;
  border: none;
  color: #596372;
}

.panel-tabs {
  padding: 4px;
  background-color: #ffffff;
  border-radius: 8px;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}

.panel-tab-btn {
  width: 50%;
  background-color: transparent;
  border: none;
  color: #20242a;
  font-size: 12px;
  padding-left: 0px;
  padding-right: 0px;
  text-transform: none;
}

.recent-selected {
  background-color: #2773F3;
  color: #ffffff;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 12px;
  padding-left: 0px;
  padding-right: 0px;
  box-shadow: 0px 3px 6px -3px rgb(0, 0, 0, 0.05),
    0px 2px 4px -2px rgba(0, 0, 0, 0.05), 0px 1px 2px -1px rgb(0, 0, 0, 0.05),
    0px 1px 0px -1px rgb(0, 0, 0, 0.05);
}

.search-selected {
  background-color: #2773F3;
  color: #ffffff;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 12px;
  padding-left: 0px;
  padding-right: 0px;
  box-shadow: 0px 3px 6px -3px rgb(0, 0, 0, 0.05),
    0px 2px 4px -2px rgba(0, 0, 0, 0.05), 0px 1px 2px -1px rgb(0, 0, 0, 0.05),
    0px 1px 0px -1px rgb(0, 0, 0, 0.05);
}
</style>
