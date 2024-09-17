<template>
  <transition :name="animation">
    <div
      v-if="showModel"
      class="tw-block tw-mt-1 tw-rounded-lg tw-absolute tw-z-10 tw-shadow-lg tw-bg-white tw-ring-1 tw-ring-inset tw-ring-gray-300">
      <slot></slot>
    </div>
  </transition>
</template>

<script>
import { defineComponent, ref, computed, onMounted, onUnmounted } from "vue";

export default defineComponent({
  props: {
    animation: {
      type: String,
      default: () => "fade",
    },
    show: {
      type: Boolean,
      default: () => false,
    },
  },
  emits: ["input", "close"],
  setup(props, { emit }) {
    const showModel = computed(() => {
      return props.show;
    });

    const addBodyListener = () => {
      emit("close", true);
    };

    onMounted(() => {
      document.body.addEventListener("click", addBodyListener);
    });

    onUnmounted(() => {
      document.body.removeEventListener("click", addBodyListener);
    });

    return {
      showModel,
    };
  },
});
</script>

<style scoped>
/* Animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.1s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}

/* Slide-in-up animation*/
.slide-in-up-enter-active,
.slide-in-up-leave-active {
  transition: all 0.5s;
  transform: translateY(0);
}
.slide-in-up-enter,
.slide-in-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Slide-in-right animation*/
.slide-in-right-enter-active,
.slide-in-right-leave-active {
  transition: all 0.5s;
  transform: translateX(0);
}
.slide-in-right-enter,
.slide-in-right-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Slide-in-left animation*/
.slide-in-left-enter-active,
.slide-in-left-leave-active {
  transition: all 0.5s;
  transform: translateX(0);
}
.slide-in-left-enter,
.slide-in-left-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

/* Scale animation*/
.scale-enter-active,
.scale-leave-active {
  transition: all 0.5s;
  transform: scale(1);
}
.scale-enter,
.scale-leave-to {
  opacity: 0;
  transform: scale(0);
}

/* Rotate animation*/
.rotate-enter-active,
.rotate-leave-active {
  transition: all 0.5s;
  transform: scale(1) rotate(-360deg);
}
.rotate-enter,
.rotate-leave-to {
  opacity: 0;
  transform: scale(0) rotate(360deg);
}
</style>
