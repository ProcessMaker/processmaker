<template>
  <div>
    <div v-if="!showEmpty">
      <div class="d-flex justify-content-center my-5">
        <img
          class="image d-flex"
          src="/img/processes-catalogue-empty.svg"
          alt="recent projects"
        >
      </div>
      <h4 v-show="!isBookmarkEmpty" class="text-center">
        {{ $t("Currently, you don't have any processes created.") }}
      </h4>
      <h4 v-show="isBookmarkEmpty" class="text-center">
        {{ $t("Currently, you don’t have any processes bookmarked.") }}
      </h4>
      <p class="text-center">
        {{ $t('We encourage you to create new processes using our templates.') }}
      </p>
      <p class="text-center my-4">
        <button
          type="button"
          class="btn btn-primary text-capitalize"
          @click="wizardLinkSelected"
        >
          {{ $t("Show Me The Templates") }}
        </button>
      </p>
    </div>
    <div v-if="showEmpty">
      <EmptySearch />
    </div>
  </div>
</template>
<script>
import EmptySearch from "./utils/EmptySearch.vue";

export default {
  components: { EmptySearch },
  props: ["showEmpty", "isBookmarkEmpty"],
  methods: {
    /**
     * go to wizard templates section
     */
    wizardLinkSelected() {
      window.ProcessMaker.EventBus.$emit(
        "wizard-templates-selected",
        {
          label: this.$t("Guided Templates"),
          selected: false,
          id: "guided_templates",
        },
      );
    },
  },
};
</script>
