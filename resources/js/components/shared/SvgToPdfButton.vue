<template>
  <button
    :class="{ 'disabled': isLoading }"
    :aria-disabled="isLoading"
    :disabled="isLoading"
    @click="convertSVGtoPDF()"
  >
    <div
      v-if="isLoading"
      class="spinner-border spinner-border-sm"
      role="status"
    />
    <div v-else>
      {{ $t("Download as PDF") }}
    </div>
  </button>
</template>
<script>
import { jsPDF } from "jspdf";
import "svg2pdf.js";

export default {
  name: "SvgToPdfButton",
  props: {
    svg: {
      type: String,
      required: true,
    },
    fileName: {
      type: String,
      default: "document",
    },
  },
  data() {
    return {
      isLoading: false,
    };
  },
  methods: {
    convertSVGtoPDF() {
      this.isLoading = true;
      // eslint-disable-next-line new-cap
      const pdf = new jsPDF({
        format: "letter",
        orientation: "landscape",
      });

      const parser = new DOMParser();
      const doc = parser.parseFromString(this.svg, "image/svg+xml");
      const svgElement = doc.firstChild;

      pdf
        .svg(svgElement, {
          x: 0,
          y: 0,
          width: pdf.internal.pageSize.width,
          height: pdf.internal.pageSize.height,
        })
        .then(() => {
          pdf.save(`${this.fileName}.pdf`);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
