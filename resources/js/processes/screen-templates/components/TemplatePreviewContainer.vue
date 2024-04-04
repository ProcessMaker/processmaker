<template>
  <div>
    <splitpane-container
      v-if="showPreview"
      :size="splitpaneSize"
    >
      <div
        id="template-preview"
        ref="template-preview"
        class="h-100 p-3"
        style="overflow-y: auto;"
      >
        <div>
          <div class="d-flex w-100 h-100 mb-3">
            <slot
              name="header"
              :close="onClose"
              :templateId="template.id"
            >
              <div class="ml-auto mr-0 text-right">
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Close')"
                  @click="onClose()"
                >
                  <i class="fas fa-times" />
                </b-button>
              </div>
            </slot>
          </div>
          <preview-template
            ref="preview"
            :template="selectedTemplate"
            :hide-back-arrow="true"
            :hide-template-options="true"
          />
        </div>
      </div>
    </splitpane-container>
  </div>
</template>

<script>
import SplitpaneContainer from "../../../tasks/components/SplitpaneContainer.vue";
import PreviewTemplate from "../../../components/templates/PreviewTemplate.vue";
import templatePreviewMixin from "../mixins/templatePreviewMixin";

export default {
  components: {
    SplitpaneContainer,
    PreviewTemplate,
  },
  mixins: [templatePreviewMixin],
  props: {
    selectedTemplate: {
      type: [Object, Array],
      default: null,
    },
  },
};
</script>

<style scoped>
#template-preview {
  box-sizing: border-box;
  display: block;
  overflow: hidden;
  position: relative;
}

</style>
