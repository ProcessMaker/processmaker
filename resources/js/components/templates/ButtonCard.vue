<template>
  <div :class="{'d-flex': button.helperEnabled}">
    <b-card no-body class="button-card" :class="{'col-6 p-0': button.helperEnabled}" @mouseenter="addHoverClass" @mouseleave="removeHoverClass" @click="$emit('card-button-clicked')">
      <div class="card-body text-center d-flex justify-content-center flex-column">
        <i v-if="button.icon" class="icon mb-2 text-primary" :class="button.icon" :style="button.iconStyle"></i>
        <img v-if="button.svgIcon" :src="button.svgIcon" class="mb-2" :style="button.svgIconStyle" :alt="button.title">
        <h5 class="m-0">{{ button.title }}</h5>
        <div v-if="button.description" class="mb-2">
          <div class="mt-1 description text-secondary">{{ button.description }}</div>
        </div>
      </div>
      <div v-if="button.showAiSlogan" class="ai-slogan-container mb-2">
        <div class="ai-slogan"><img src="/img/favicon.svg" :alt="$t('ProcessMaker')"> {{ $t("Powered by ProcessMaker AI") }}</div>
      </div>
    </b-card>
  </div>
</template>

<script>

export default {
  components: {},
  props: ["button", "large"],
  data() {
    return {
    };
  },
  methods: {
    addHoverClass(event) {
      event.target.classList.add("hover");
    },
    removeHoverClass(event) {
      event.target.classList.remove("hover");
    },
  },
};
</script>

<style lang="scss" scoped>
.button-card {
  height: 172px;
  border-radius: 4px;
  padding: 10px 8px 10px 8px;
  overflow: hidden;
  border: 2px solid rgba(0, 0, 0, 0.125);
}

.button-card.large {
  width: 583px;
}

.card-title {
  font-weight: 600;
  font-size: 14px;
}

.card-img {
  background: #80808017;
  height: 112px;
  display: flex;
  align-items: center;
}

.card-body {
  padding: 2px!important;
}

.card-text {
  font-size: 12px;
  color: #6C757D;
}

.category-badge {
  background-color: #DEEBFF;
  color: #104A75;
  font-size: 12px;
}

.hover {
  border-color: #1572C2;
  cursor: pointer;
}

.icon {
  color: #104A75;
  font-size: 1.5rem;
}

.description {
  font-weight: 200;
  font-size: 14px;
}

.ai-slogan-container {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  text-align: center;
}
.ai-slogan {
  font-size: 70%;
  font-weight: 600;
}
.ai-slogan > img {
  height: 16px;
}

@media (min-width: 576px) {
  .card-deck .card {
    margin-left: 9px;
    margin-right: 9px;
  }
}
</style>
