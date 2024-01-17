<template>
  <div>
    <b-card 
      no-body 
      class="wizard-template-select-card p-0"
      :style="{ backgroundImage: 'url(' + cardBackgroundImage + ')' }"
      @click="showDetails()"
      @mouseenter="addHoverClass"
      @mouseleave="removeHoverClass"
    >
      <b-card-body class="p-2">
        <div class="wizard-icon-container text-right mb-3">
          <img src="../../../img/wizard-template-icon.svg" alt="Guided Template Icon">
        </div>
        <b-card-text class="mx-4">
          <img :src="templateIcon" :alt="template.name + ' icon'" width="45px" class="mb-3"/>
          <h5 class="text-uppercase">{{ template.name | str_limit(30) }}</h5>
          {{ template.description | str_limit(150) }}
        </b-card-text>
      </b-card-body>
    </b-card>
  </div>
</template>

<script>
import templateMixin from './mixins/template.js';

export default {
  components: { },
  mixins: [templateMixin],
  props: ["template"],
  computed: {
    cardBackgroundImage() {
      return this.template?.template_media?.cardBackground;
    },
    templateIcon() {
      return this.template?.template_media?.icon;
    }
  },
};
</script>

<style lang="scss" scoped>
.wizard-template-select-card {
  border-radius: 16px;
  border: 1px solid #CDDDEE;
  background-size: cover;
  width: 350px;
  height: 240px;
  overflow: hidden;
  &:before {
    content: "";
    background-color: #ffff;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: .5;
  }
  &.hover {
    box-shadow: 0px 10px 20px 4px #00000021;
    cursor: pointer;
  }

  .card-body {
    z-index: 2;
    .card-text {
      font-size: 15px;
      color: #556271;
      h5 {
          font-weight: 700;
      }
    }
  }
  
}

@media (min-width: 576px) {
  .card-deck .card {
    margin-left: 9px;
    margin-right: 9px;
  }
}

</style>