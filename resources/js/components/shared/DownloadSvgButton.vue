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
    async download() {
      this.isLoading = true;
      const svgString = await this.convertImagesToBase64(this.svg);
      const canvas = await this.svgToCanvas(svgString, this.watermarkText);
      const dataURL = canvas.toDataURL("image/png");

      const downloadLink = document.createElement("a");
      downloadLink.href = dataURL;
      downloadLink.download = `${this.fileName}.png`;

      document.body.appendChild(downloadLink);
      downloadLink.click();

      document.body.removeChild(downloadLink);
      this.isLoading = false;
    },

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
      const shiftDown = 90;

      canvas.width = originalWidth;
      canvas.height = originalHeight + watermarkHeight + padding + shiftDown;

      // Remove transparency from the canvas.
      ctx.fillStyle = "white";
      ctx.fillRect(0, 0, canvas.width, canvas.height);

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
      ctx.fillStyle = "black";
      const textWidth = ctx.measureText(watermarkText).width;

      // Calculate the total width of the image + text
      const totalWidth = watermarkImage.width + textWidth + padding;

      // Draw the watermark image to the left of the text.
      ctx.drawImage(watermarkImage, canvas.width - totalWidth, originalHeight + shiftDown, watermarkImage.width, watermarkHeight);

      // Adjusted the y-coordinate so the watermark sits just below the SVG content.
      ctx.fillText(watermarkText, canvas.width - textWidth - padding, originalHeight + shiftDown + watermarkHeight - padding);

      return canvas;
    },

    async convertImagesToBase64(svgString) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(svgString, "image/svg+xml");
      const images = doc.querySelectorAll("image");

      // Convert URL image to base64.
      async function fetchImageAsBase64(url) {
        const response = await fetch(url);
        const blob = await response.blob();
        return new Promise((resolve) => {
          const reader = new FileReader();
          reader.onload = () => resolve(reader.result);
          reader.readAsDataURL(blob);
        });
      }

      // eslint-disable-next-line no-restricted-syntax
      for (const img of images) {
        const href = img.getAttributeNS("http://www.w3.org/1999/xlink", "href");
        if (href && href.startsWith("http")) {
          // eslint-disable-next-line no-await-in-loop
          const base64Data = await fetchImageAsBase64(href);
          img.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href", base64Data);
        }
      }

      return new XMLSerializer().serializeToString(doc);
    },
  },
};
</script>
