<template>
  <transition :name="transitionName">
    <div class="tw-flex tw-h-full tw-absolute">
      <!-- Overlay: semi-transparent background, closes drawer on click -->
      <transition name="fade">
        <div
          v-if="isOpen"
          class="tw-absolute tw-inset-0 tw-bg-black tw-bg-opacity-50 tw-z-40 tw-transition-opacity tw-duration-300"
          @click.prevent="closeDrawer">
          <button
            class="tw-absolute tw-top-2 tw-right-2 tw-size-8 tw-flex tw-items-center tw-justify-center tw-rounded-md tw-bg-white hover:tw-bg-gray-100 tw-transition-colors tw-duration-200 tw-cursor-pointer"
            @click.prevent="closeDrawer"
            aria-label="Close drawer">
            <i class="fa fa-times tw-text-base tw-text-gray-600" />
          </button>
        </div>
      </transition>

      <!-- Drawer Modal Panel: slides in based on the position prop -->
      <div
        v-if="isOpen"
        class="tw-absolute tw-w-[80%] tw-z-50 tw-shadow-2xl tw-overflow-y-auto tw-transition-transform tw-duration-300"
        :class="drawerClass">
        <!-- Main Drawer Content -->
        <div class="tw-h-full">
          <slot name="default"/>
        </div>

        <!-- Drawer Footer if slot provided -->
        <div
          v-if="$slots.footer"
          class="tw-p-4 tw-border-t tw-border-solid tw-border-gray-200">
          <slot name="footer"/>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
import { computed, toRefs, defineComponent } from "vue";

const validPositions = ["left", "right", "top", "bottom"];
const validButtonPositions = [
  "top-left",
  "top-right",
  "bottom-left",
  "bottom-right"
];

export default defineComponent({
  name: "MobileDrawer",
  props: {
    value: {
      type: Boolean,
      default: false,
    },
    position: {
      type: String,
      default: "left",
      validator: val => validPositions.includes(val),
    },
    title: {
      type: String,
      default: "Menu",
    },
    buttonPosition: {
      type: String,
      default: "top-left",
      validator: val => validButtonPositions.includes(val),
    },
  },
  setup(props, { emit }) {
    const { value, position } = toRefs(props);

    /**
     * Open state, two-way bound to v-model
     */
    const isOpen = computed({
      get: () => value.value,
      set: val => emit("input", val),
    });

    /**
     * Computed classes for drawer position/size
     */
    const drawerClass = computed(() => ({
      "tw-top-0 tw-left-0 tw-h-full": position.value === "left",
      "tw-top-0 tw-right-0 tw-h-full": position.value === "right",
      "tw-top-0 tw-left-0 tw-max-h-[50vh]": position.value === "top",
      "tw-bottom-0 tw-left-0 tw-max-h-[50vh]": position.value === "bottom",
    }));

    /**
     * Transition name depending on drawer position
     */
    const transitionName = computed(() => {
      const transitions = {
        left: "slide-left",
        right: "slide-right",
        top: "slide-top",
        bottom: "slide-bottom",
      };
      return transitions[position.value] || "slide-left";
    });

    /**
     * Open the drawer (emit events)
     */
    const openDrawer = () => {
      isOpen.value = true;
      emit("change", true);
      emit("open");
    };

    /**
     * Close the drawer (emit events)
     */
    const closeDrawer = () => {
      isOpen.value = false;
      emit("change", false);
      emit("close");
    };

    return {
      isOpen,
      transitionName,
      drawerClass,
      openDrawer,
      closeDrawer,
    };
  }
});
</script>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active,
.slide-top-enter-active,
.slide-top-leave-active,
.slide-bottom-enter-active,
.slide-bottom-leave-active {
  transition: transform 0.3s ease;
}
.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(-100%);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-right-enter-from,
.slide-right-leave-to {
  transform: translateX(100%);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-top-enter-from,
.slide-top-leave-to {
  transform: translateY(-100%);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-bottom-enter-from,
.slide-bottom-leave-to {
  transform: translateY(100%);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
  -webkit-transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  -webkit-opacity: 0;
}
</style>
