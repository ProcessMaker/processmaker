<template>
  <div class="h-100">
    <b-card
      id="app"
      no-body
      class="h-100 bg-white border-top-0"
    >
      <b-card-body
        id="screen-builder-container"
        class="overflow-auto p-0 h-100"
      >
        <vue-form-renderer
          ref="renderer"
          :key="rendererKey"
          v-model="previewData"
          class="p-3"
          :mode="mode"
          :config="preview.config"
          :computed="preview.computed"
          :custom-css="preview.custom_css"
          :watchers="preview.watchers"
          :show-errors="true"
          :device-screen="deviceScreen"
          @submit="previewSubmit"
          @css-errors="cssErrors = $event"
        />
      </b-card-body>
    </b-card>
    <!-- Modals -->
  </div>
</template>

<script>
import { VueFormRenderer } from "@processmaker/screen-builder";
import "@processmaker/vue-form-elements/dist/vue-form-elements.css";

export default {
  components: {
    VueFormRenderer,
  },
  props: {
    screen: {
      type: Object,
      required: true,
    },
  },
  data() {
    const defaultConfig = [
      {
        name: "Default",
        computed: [],
        items: [],
      },
    ];


    return {
      preview: {
        config: [
          {
            name: "Default",
            computed: [],
            items: [],
          },
        ],
        computed: [],
        custom_css: "",
        watchers: [],
      },
      type: "form",
      mode: "editor",
      deviceScreen: "desktop",
      previewData: {},
      cssErrors: "",
      rendererKey: 0,
    };
  },
  mounted() {
    this.preview = this.screen;
  },
  methods: {
    previewSubmit() {
      // eslint-disable-next-line no-alert
      alert("Preview Form was Submitted");
    },
  },
};
</script>

<style lang="scss">
$primary-white: #f7f7f7;

html,
body {
  height: 100%;
  min-height: 100%;
  max-height: 100%;
  overflow: hidden;
}
</style>
