<template>
    <div>
      <p class="text-muted">{{ template.description }}</p>
      <div>
        <p class="text-muted">{{ $t("Created By:") }}
          <avatar-image
            size="25"
            :hideName="false"
            :input-data="template.user"
          ></avatar-image>
        </p>
      <b-badge pill variant="success" class="category-badge mb-3">
        Category 1
      </b-badge>
      </div>
      <div id="svg-container" v-html="svg"></div>
    </div>
</template>

<script>
import { Modal } from "SharedComponents";
import AvatarImage from '../AvatarImage.vue';
import svgPanZoom from 'svg-pan-zoom';

  export default {
    components: { Modal, AvatarImage },
    mixins: [ ],
    props: ['template'],
    data: function() {
      return {
      }
    },
    methods: {},
    computed: {
      svg() {
        let parser = new DOMParser();
        let svg = parser.parseFromString(this.template.svg, "image/svg+xml");
        return svg.documentElement.outerHTML;
      }
    },   
    mounted() {
      svgPanZoom('#svg-container > svg', {
        controlIconsEnabled: true,
        fit: true,
        center: true,
        contain: true,
      }); 
    }
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
    border: 1px solid #104A75;
  }

  #svg-container {
    height: 23vh;
    /* height: 51vh; */
    background: #fafafa;
    cursor: all-scroll;
  }
</style>
