<template>
  <div>
    <div id="svg-container" v-html="svg" class="d-flex justify-content-center align-items-center mb-2"></div>
    <div>
      <p class="mb-2">
        <span class="text-muted">{{ $t("Created By:") }}</span>
        <avatar-image
          size="25"
          :hideName="false"
          :input-data="template.user"
        />
      </p>
      <p class="mb-2" v-if="template.version">
        <span class="text-muted">{{ $t("Version: ") }} {{template.version}} </span>
      </p>
      <b-badge pill v-for="category in categories" :key="category.id" class="category-badge mb-2 mr-1">
        {{ category.name }}
      </b-badge>
    </div>
    <p class="">{{ template.description }}</p>
  </div>
</template>

<script>

import svgPanZoom from 'svg-pan-zoom';
import Modal from "../../components/shared/Modal.vue";
import AvatarImage from '../AvatarImage.vue';

export default {
  components: { Modal, AvatarImage },
  props: ["template"],
  computed: {
    svg() {
      if (this.template.svg === null) {
        return "Sorry, the template preview is unavailable. Please try resaving the template.";
      }

      let parser = new DOMParser();
      let svg = parser.parseFromString(this.template.svg, "image/svg+xml");

      return svg.documentElement.outerHTML;
    },
    categories() {
      return this.catLimit ? this.template.categories.slice(0,this.catLimit) : this.template.categories;
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
    background-color: #1572C2;
  }

  .category-badge {
    background-color: #DEEBFF;
    color: #104A75;
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
