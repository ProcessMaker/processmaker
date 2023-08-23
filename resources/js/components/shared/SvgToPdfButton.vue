<template>
  <a
    href="#"
    role="button"
    :class="{ 'btn-disabled': isLoading }"
    :aria-disabled="isLoading"
    :disabled="isLoading"
    @click="download()"
  >
    <span
      v-if="isLoading"
      class="spinner-border spinner-border-sm"
      role="status"
    />
    <span v-else>
      {{ $t(buttonLabel) }}
    </span>
  </a>
</template>
<script>
// eslint-disable-next-line import/no-extraneous-dependencies
import { Canvg } from "canvg";

export default {
  props: {
    svg: {
      type: String,
      required: true,
    },
    fileName: {
      type: String,
      default: "document",
    },
    buttonLabel: {
      type: String,
      default: "Download",
    },
  },
  data() {
    return {
      isLoading: false,
    };
  },
  methods: {
    async svgToCanvas(svgString) {
      // Create canvas.
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");

      // Set canvas dimensions from SVG attributes.
      const svgElement = new DOMParser().parseFromString(svgString, "image/svg+xml").documentElement;
      canvas.width = svgElement.getAttribute("width");
      canvas.height = svgElement.getAttribute("height");

      // Render SVG onto canvas.
      const v = await Canvg.fromString(ctx, svgString);
      v.render();

      return canvas;
    },

    async download() {
      this.isLoading = true;
      const canvas = await this.svgToCanvas(this.svg);
      const dataURL = canvas.toDataURL("image/png");

      const downloadLink = document.createElement("a");
      downloadLink.href = dataURL;
      downloadLink.download = `${this.fileName}.png`;

      document.body.appendChild(downloadLink);
      downloadLink.click();

      document.body.removeChild(downloadLink);
      this.isLoading = false;
    },
  },
};
</script>
