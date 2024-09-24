<template>
  <div
    ref="inputRef"
    class="tw-relative">
    <slot
      v-bind="{
        toogleShow,
        data,
      }"
      name="input">
      <button
        class="tw-flex tw-w-full tw-justify-between tw-items-center tw-py-2 tw-px-3 tw-ring-1 tw-ring-inset
        tw-ring-gray-300 tw-rounded-lg focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-primary-400"
        @click.prevent.stop="toogleShow">
        <span>{{ data?.label || data?.value }} </span>

        <i class="fas fa-chevron-down" />
      </button>
    </slot>

    <transition :name="animation">
      <div
        v-if="show"
        ref="containerRef"
        class="tw-fixed tw-mt-1 tw-rounded-lg tw-z-10 tw-shadow-lg tw-bg-white tw-ring-1 tw-ring-inset tw-ring-gray-300"
        :style="{ width: `${widthContainer}px`, top: `${top}px` }">
        <ul class="tw-list-none tw-overflow-hidden tw-rounded">
          <slot
            name="options"
            v-bind="{
              options: optionsModel,
              data,
              onClick,
            }">
            <li
              v-for="(option, index) in optionsModel"
              :key="index"
              class="hover:tw-bg-gray-200"
              @click.prevent.stop="onClick(option, index)">
              <slot
                name="option"
                v-bind="{
                  option,
                  data,
                }">
                <span
                  :class="{
                    'tw-bg-gray-300': option?.value === data?.value,
                  }"
                  class="tw-flex tw-py-2 tw-px-4 transition duration-300">
                  {{ option.label || option.value }}
                </span>
              </slot>
            </li>
          </slot>
        </ul>
      </div>
    </transition>
  </div>
</template>

<script>
import {
  defineComponent, ref, computed, onMounted, onUnmounted, nextTick,
} from "vue";
/**
 * <Dropdown
 *  animation="fade"
 *  :options='{"label":"Label 1" , "value": "1"}'
 *  :value="model"
 * />
 *
 * slot::input -> dropdown button
 * slot:option -> Custom component dropdown option
 */

export default defineComponent({
  props: {
    animation: {
      type: String,
      default: () => "fade",
    },
    // Example input options {"label":"Label 1" , "value": "1"}
    options: {
      type: Array,
      default: () => [],
    },
    value: {
      type: Object,
      default: () => null,
    },
  },
  emits: ["input", "change"],
  setup(props, { emit }) {
    const inputRef = ref();
    const containerRef = ref();

    const show = ref(false);
    const widthContainer = ref(100);
    const top = ref(0);

    const data = computed({
      get() {
        return props.value;
      },
      set(value) {
        emit("input", value);
      },
    });

    const optionsModel = computed({
      get() {
        return props.options;
      },
      set(val) {
        emit("update:options", val);
      },
    });

    const onClick = (option) => {
      show.value = false;
      data.value = option;

      emit("change", option);
    };

    /**
     * This method calculates the new position for dropdown menu
     * Considers:
     * - Space down in relation to viewport
     *
     * Increase other use cases
     */
    const calculatePosition = () => {
      const rect = inputRef.value?.getBoundingClientRect();
      const containerRect = containerRef.value.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      widthContainer.value = rect.width || 100;

      let container = inputRef.value.parentElement;
      let topPosition = 0;

      while (container && container !== document.body) {
        // If the container has scroll, we add their offsets
        if (container.scrollTop || container.scrollLeft) {
          topPosition += container.scrollTop;
        }
        container = container.parentElement;
      }

      // Check: Does the dropdown menu have space down?
      if (rect.top + containerRect.height > viewportHeight) {
        topPosition = rect.top - containerRect.height;

        top.value = topPosition;
        return;
      }

      top.value = inputRef.value.offsetTop - topPosition;
    };

    const addBodyListener = () => {
      show.value = false;
    };

    const toogleShow = ($event) => {
      show.value = !show.value;

      nextTick(() => {
        calculatePosition($event);
      });
    };

    onMounted(() => {
      document.body.addEventListener("click", addBodyListener);
    });

    onUnmounted(() => {
      document.body.removeEventListener("click", addBodyListener);
    });

    return {
      containerRef,
      show,
      data,
      inputRef,
      optionsModel,
      top,
      widthContainer,
      onClick,
      toogleShow,
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
