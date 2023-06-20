<template>
  <div class="pb-2">
    <b-card no-body class="button-card" :class="{'large': large}" @mouseenter="addHoverClass" @mouseleave="removeHoverClass" @click="$emit('card-button-clicked')">
      <div v-if="large" class="card-body d-flex justify-content-center flex-row justify-content-around">
        <div class="text-center d-flex justify-content-center flex-column">
          <i class="icon mb-1" :class="button.icon"></i>
          <h5 class="m-0">{{ button.title }}</h5>
        </div>
        <div class="d-flex align-items-center text-muted">
          <small>{{ button.description }}</small>
        </div>
      </div>
      <div v-else class="card-body text-center d-flex justify-content-center flex-column">
        <i class="icon mb-1" :class="button.icon"></i>
        <h5 class="m-0">{{ button.title }}</h5>
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
  width: 277px;
  height: 180px;
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

</style>
