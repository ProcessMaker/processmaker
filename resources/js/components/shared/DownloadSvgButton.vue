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
    watermarkText: {
      type: String,
      default: "Powered by ProcessMaker",
    },
  },
  data() {
    return {
      isLoading: false,
    };
  },
  methods: {
    async svgToCanvas(svgString, watermarkText) {
      // Create canvas.
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");

      // Set canvas dimensions from SVG attributes.
      const svgElement = new DOMParser().parseFromString(svgString, "image/svg+xml").documentElement;
      const originalWidth = parseFloat(svgElement.getAttribute("width"));
      const originalHeight = parseFloat(svgElement.getAttribute("height"));

      // Set new dimensions with extra space for the watermark.
      const watermarkHeight = 30;
      const padding = 5;

      canvas.width = originalWidth;
      canvas.height = originalHeight + watermarkHeight + padding;

      // Convert SVG string to data URL.
      const imgSrc = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svgString)}`;
      const img = new Image();
      img.src = imgSrc;
      await new Promise((resolve) => {
        img.onload = () => resolve();
      });

      // Draw SVG on canvas using specific dimensions.
      ctx.drawImage(img, 0, 0, originalWidth, originalHeight);

      // Load the watermark image.
      const watermarkImage = new Image();
      watermarkImage.src = "/img/icon-pmlogo.png";
      await new Promise((resolve) => {
        watermarkImage.onload = () => resolve();
      });

      // Draw watermark.
      ctx.font = "20px Arial";
      const textWidth = ctx.measureText(watermarkText).width;

      // Calculate the total width of the image + text
      const totalWidth = watermarkImage.width + textWidth + padding;

      // Draw the watermark image to the left of the text.
      // eslint-disable-next-line max-len
      ctx.drawImage(watermarkImage, canvas.width - totalWidth, canvas.height - watermarkHeight, watermarkImage.width, watermarkHeight);

      // Adjusted the y-coordinate so the watermark sits just below the SVG content.
      ctx.fillText(watermarkText, canvas.width - textWidth - padding, canvas.height - padding);

      return canvas;
    },

    async download() {
      this.isLoading = true;
      const canvas = await this.svgToCanvas(this.svg, this.watermarkText);
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
