<template>
  <div :class="{'d-flex': button.helperEnabled}">
    <b-card no-body class="button-card" @mouseenter="addHoverClass" @mouseleave="removeHoverClass" @click="$emit('card-button-clicked')">
      <div class="card-body text-center d-flex justify-content-center flex-column">
        <i class="icon mb-1 text-primary" :class="button.icon"></i>
        <h5 class="m-0">{{ button.title }}</h5>
      </div>
    </b-card>

    <div v-if="button.helperEnabled" class="helper-container">
      <div class="arrow-left"></div>
      <b-card no-body class="button-card card-helper p-4">
        <div class="card-body card-helper text-center d-flex justify-content-center flex-column">
          <h4 class="m-0 text-left card-title">{{ button.helperTitle }}</h4>
          <p class="m-0 text-left card-text">{{ button.helperDescription }}</p>
        </div>
      </b-card>
    </div>
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
  width: 292px;
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

.helper-container {
  position: relative;
}
.arrow-left {
  width: 0;
  height: 0;
  border-top: 16px solid transparent;
  border-bottom: 16px solid transparent;
  border-right: 13px solid #BCDCF8;
  position: absolute;
  top: calc(50% - 16px);
  left: 0px;
}
.button-card.card-helper {
    border: 0;
    background: #BCDCF8;
}
.button-card.card-helper .card-text {
  color:#363A3E;
}

@media (min-width: 576px) {
  .card-deck .card {
    margin-left: 9px;
    margin-right: 9px;
  }
}
</style>
