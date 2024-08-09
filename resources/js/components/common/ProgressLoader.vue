<template>
  <div
    :class="{'d-flex align-items-center justify-content-center progress-backdrop': backdrop}"
    :style="`background: ${backdropColor};`"
  >
    <div
      v-if="isRadial"
      class="d-flex flex-column justify-content-center"
    >
      <div
        class="progress-circle"
        :style="`width: ${width};`"
      >
        <svg :width="width" viewBox="0 0 100 100">
          <circle
            class="bg"
            :style="`stroke: ${backgroundColor};`"
            cx="50"
            cy="50"
            r="45"
          />
          <circle
            class="progress-circle-inner"
            :style="`stroke: ${progressColor};`"
            cx="50"
            cy="50"
            r="45"
          />
        </svg>
      </div>
      <div
        v-if="loadingText"
        class="text-center mt-1 loading-text"
        :style="`font-size: ${textSize};`"
      >
        {{ loadingText }}
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    type: {
      type: String,
      default: "radial",
    },
    width: {
      type: String,
      default: "80px",
    },
    height: {
      type: String,
      default: "100%",
    },
    progressColor: {
      type: String,
      default: "#2773F3",
    },
    backgroundColor: {
      type: String,
      default: "#CDDDEE",
    },
    backdropColor: {
      type: String,
      default: "rgba(255, 255, 255, 0.65)",
    },
    spin: {
      type: Boolean,
      default: true,
    },
    backdrop: {
      type: Boolean,
      default: true,
    },
    loadingText: {
      type: String,
      default: "Loading",
    },
    textSize: {
      type: String,
      default: "1rem",
    },
  },

  data() {
    return {
    };
  },
  computed: {
    isRadial() {
      return this.type === "radial";
    },
    isProgressBar() {
      return this.type === "bar";
    },
  },
  mounted() {
  },
  methods: {
  },
};
</script>

<style lang="scss" scoped>
.progress-circle {
  position: relative;
}

.progress-circle svg {
  transform: rotate(-90deg);
  animation: rotation 1.5s linear infinite;
}

.progress-circle circle {
  fill: none;
  stroke-width: 10;
}

.progress-circle .progress-circle-inner {
  stroke-linecap: round;
  stroke-dasharray: 280;
  stroke-dashoffset: 100;
  transition: stroke-dashoffset 0.3s ease;
}

.progress-backdrop {
  position: absolute;
  z-index: 100;
  width: 100%;
  height: 100%;
  left: 0;
  top: 0;
}

.loading-text {
  color: #556271;
  font-weight: 600;
}

@keyframes rotation {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
