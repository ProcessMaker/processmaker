<template>
  <div>
    <div
      id="svg-container"
      class="d-flex justify-content-center align-items-center mb-2"
      v-html="svg"
    />
    <div>
      <p class="mb-2">
        <span class="text-muted">{{ $t("Created By:") }}</span>
        <avatar-image
          size="25"
          :hide-name="false"
          :input-data="template.user"
        />
      </p>
      <p
        v-if="template.version"
        class="mb-2"
      >
        <span
          class="text-muted"
        >{{ $t("Version: ") }} {{ template.version }}
        </span>
      </p>
      <b-badge
        v-for="category in categories"
        :key="category.id"
        pill
        class="category-badge mb-2 mr-1"
      >
        {{ category.name }}
      </b-badge>
    </div>
    <p class="">
      {{ template.description }}
    </p>
  </div>
</template>

<script>
import svgPanZoom from "svg-pan-zoom";
import { Modal } from "../shared";
import AvatarImage from "../AvatarImage.vue";

export default {
  components: { Modal, AvatarImage },
  props: ["template"],
  computed: {
    svg() {
      if (this.template.svg === null) {
        return "Sorry, the template preview is unavailable. Please try resaving the template.";
      }

      const parser = new DOMParser();
      const svg = parser.parseFromString(this.template.svg, "image/svg+xml");

      return svg.documentElement.outerHTML;
    },
    categories() {
      return this.catLimit
        ? this.template.categories.slice(0, this.catLimit)
        : this.template.categories;
    },
  },
  mounted() {
    if (this.template.svg === null) {
      return;
    }
    svgPanZoom("#svg-container > svg", {
      controlIconsEnabled: true,
      fit: true,
      center: true,
      contain: true,
    });
  },
};
</script>

<style scoped>
.blank-template-btn {
  float: right;
  background-color: #1572c2;
}

.category-badge {
  background-color: #deebff;
  color: #104a75;
  font-size: 12px;
}

#svg-container {
  height: 23vh;
  /* height: 51vh; */
  background: #fafafa;
  cursor: all-scroll;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}
</style>
