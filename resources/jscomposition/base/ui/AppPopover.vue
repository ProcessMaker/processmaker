<template>
  <div
    ref="tooltipWrapper"
    class="tw-relative"
    @mouseenter="($event) => hover && showTooltip($event)"
    @mouseleave="($event) => hover && hideTooltip($event)">
    <slot name="default" />
    <div
      v-if="visible"
      ref="tooltip"
      class="tooltip-content"
      :style="{
        top: `${tooltipPosition.top}px`,
        left: `${tooltipPosition.left}px`,
      }"
      :class="calculatedPosition">
      <slot name="content">
        {{ content }}
      </slot>
    </div>
  </div>
</template>

<script>
import {
  ref, watch, onMounted, nextTick, onUnmounted, computed,
} from "vue";

export default {
  props: {
    content: {
      type: String,
    },
    hover: {
      type: Boolean,
      default: false,
    },
    position: {
      type: String,
      default: "top", // options: 'top', 'bottom', 'left', 'right'
      validator: (value) => ["top", "bottom", "left", "right"].includes(value),
    },
    value: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { emit }) {
    const tooltip = ref(null);
    const tooltipWrapper = ref(null);
    const tooltipPosition = ref({ top: 0, left: 0 });
    const visible = props.hover
      ? ref(false)
      : computed({
        get: () => props.value,
        set: (val) => {
          emit("input", val);
        },
      });
    const calculatedPosition = ref(props.position);

    const calculatePosition = () => {
      const wrapperRect = tooltipWrapper.value.getBoundingClientRect();
      const tooltipRect = tooltip.value.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      let top = 0;
      let left = 0;

      // Position calculated dynamicaly
      switch (props.position) {
        case "top":
          top = wrapperRect.top - tooltipRect.height - 10;
          left = wrapperRect.left + wrapperRect.width / 2 - tooltipRect.width / 2;
          // Verify space top
          if (top < 0) {
            calculatedPosition.value = "bottom";
            top = wrapperRect.bottom + 10;
          }
          break;
        case "left":
          top = wrapperRect.top + wrapperRect.height / 2 - tooltipRect.height / 2;
          left = wrapperRect.left - tooltipRect.width - 10;
          // Verify space left
          if (left < 0) {
            calculatedPosition.value = "right";
            left = wrapperRect.right + 10;
          }
          break;
        case "right":
          top = wrapperRect.top + wrapperRect.height / 2 - tooltipRect.height / 2;
          left = wrapperRect.right + 10;
          // Verify space right
          if (left + tooltipRect.width > viewportWidth) {
            calculatedPosition.value = "left";
            left = wrapperRect.left - tooltipRect.width - 10;
          }
          break;
        case "bottom":
        default:
          top = wrapperRect.bottom + 10;
          left = wrapperRect.left + wrapperRect.width / 2 - tooltipRect.width / 2;
          // Verify space bottom
          if (top + tooltipRect.height > viewportHeight) {
            calculatedPosition.value = "top";
            top = wrapperRect.top - tooltipRect.height - 10;
          }

          const leftaux = wrapperRect.left - tooltipRect.width - 10;
          // Verify space left
          if (leftaux < 0) {
            calculatedPosition.value = "right-bottom";
            left = wrapperRect.right + 100;
          }
      }

      // Verify if tooltip leaves the viewport
      top = Math.max(10, Math.min(top, viewportHeight - tooltipRect.height - 10));
      left = Math.max(10, Math.min(left, viewportWidth - tooltipRect.width - 10));

      tooltipPosition.value = { top, left };
    };

    const showTooltip = () => {
      visible.value = true;
      nextTick(() => {
        calculatePosition();
      });
    };

    const hideTooltip = () => {
      visible.value = false;
    };

    const clickIntoPopover = (event) => {
      if (!tooltipWrapper.value.contains(event.target)) {
        hideTooltip();
      }
    };

    // Recalculated position when property visible changes
    const unwatch = watch(
      () => props.value,
      (newVal) => {
        if (newVal) {
          showTooltip();
        } else {
          hideTooltip();
        }
      },
    );

    onMounted(() => {
      if (props.visible) {
        showTooltip();
      }

      !props.hover && document.body.addEventListener("click", clickIntoPopover);
    });

    onUnmounted(() => {
      document.body.removeEventListener("click", clickIntoPopover);
      unwatch();
    });

    return {
      tooltip,
      tooltipWrapper,
      tooltipPosition,
      visible,
      calculatedPosition,
      showTooltip,
      hideTooltip,
    };
  },
};
</script>

<style scoped>
.tooltip-content {
  position: fixed;
  z-index: 1000;
}

.tooltip-content.top {
  transform: translateX(-50%);
}

.tooltip-content.bottom {
  transform: translateX(-50%);
}

.tooltip-content.left {
  transform: translateY(-50%);
}

.tooltip-content.right {
  transform: translateY(-50%);
}

.tooltip-content.right-bottom {
  transform: translateY(-50%);
  transform: translateX(-50%);
}
</style>
