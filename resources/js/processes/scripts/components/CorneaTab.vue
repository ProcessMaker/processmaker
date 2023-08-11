<template>
  <div>
    <b-list-group-item class="script-toggle border-0 mb-0">
      <b-row v-b-toggle.assistant>
        <b-col v-if="!showPromptArea">
          <img :src="corneaIcon" />
          {{ $t("Cornea AI Assistant") }}
        </b-col>
        <b-col v-else @click="showPromptArea = false">
          <i class="fa fa-arrow-left" />
          {{ $t("Generate Script From Text") }}
        </b-col>
        <b-col v-if="!showPromptArea" class="bg-warning rounded" cols="3">{{
          $t("New")
        }}</b-col>
        <b-col align-self="end" cols="1" class="mr-2">
          <i class="fas fa-chevron-down accordion-icon" />
        </b-col>
      </b-row>
    </b-list-group-item>
    <b-list-group-item
      class="p-0 border-left-0 border-right-0 border-top-0 mb-0"
    >
      <b-collapse id="assistant">
        <div v-if="!showPromptArea">
          <div class="card-header m-0 d-flex border-0 pb-1">
            <div
              class="d-flex w-50 p-2 ai-button-container"
              @click="showPromptArea = true"
            >
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
              >
                <div>
                  <img :src="penSparkleIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Generate Script From Text") }}
                </div>
              </div>
            </div>
            <div
              class="d-flex w-50 p-2 ai-button-container"
              @click="$emit('documentScript')"
            >
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
              >
                <div>
                  <img :src="bookIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Document") }}
                </div>
              </div>
            </div>
          </div>

          <div class="card-header m-0 d-flex border-0 pt-0">
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
              >
                <div>
                  <img :src="brushIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Clean") }}
                </div>
              </div>
            </div>
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
              >
                <div>
                  <img :src="listIcon" />
                </div>
                <div class="text-center">
                  {{ $t("List Steps") }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <generate-script-text-prompt v-else />
      </b-collapse>
    </b-list-group-item>
  </div>
</template>
<script>
import GenerateScriptTextPrompt from "./GenerateScriptTextPrompt.vue";

export default {
  name: "CorneaTab",
  components: {
    GenerateScriptTextPrompt,
  },
  props: ["user"],
  data() {
    return {
      showPromptArea: false,
      corneaIcon: require("./../../../../img/cornea_icon.svg"),
      penSparkleIcon: require("./../../../../img/pen_sparkle_icon.svg"),
      bookIcon: require("./../../../../img/book_icon.svg"),
      brushIcon: require("./../../../../img/brush_icon.svg"),
      listIcon: require("./../../../../img/list_icon.svg"),
    };
  },
};
</script>
<style>
.script-toggle {
  cursor: pointer;
  user-select: none;
  background: #f7f7f7;
}

.accordion-icon {
  transition: all 200ms;
}

.ai-button-container {
  height: 8rem;
}

.ai-button {
  border-radius: 8px;
  box-shadow: 0 0 8px 0px #ddd;
}
.ai-button:hover {
  background: #f5f5f5 !important;
}
</style>