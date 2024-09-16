<template>
  <div
    class="tooltip-wrapper"
    ref="tooltipWrapper"
    @mouseenter="showTooltip"
    @mouseleave="hideTooltip">
    <slot name="default"></slot>
    <div
      v-if="visible"
      class="tooltip-content"
      :style="{
        top: `${tooltipPosition.top}px`,
        left: `${tooltipPosition.left}px`,
      }"
      :class="calculatedPosition"
      ref="tooltip">
      <slot name="content">
        {{ content }}
      </slot>
    </div>
  </div>
</template>

<script>
import { ref, watch, onMounted, nextTick, onUnmounted } from "vue";

export default {
  props: {
    content: {
      type: String,
    },
    position: {
      type: String,
      default: "top", // options: 'top', 'bottom', 'left', 'right'
      validator: (value) => ["top", "bottom", "left", "right"].includes(value),
    },
    visible: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const tooltip = ref(null);
    const tooltipWrapper = ref(null);
    const tooltipPosition = ref({ top: 0, left: 0 });
    const visible = ref(false);
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
        case "bottom":
          top = wrapperRect.bottom + 10;
          left = wrapperRect.left + wrapperRect.width / 2 - tooltipRect.width / 2;
          // Verify space bottom
          if (top + tooltipRect.height > viewportHeight) {
            calculatedPosition.value = "top";
            top = wrapperRect.top - tooltipRect.height - 10;
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
      }

      //Verify if tooltip leaves the viewport
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

    // Recalculated position when property visible changes
    const unwatch = watch(() => props.visible, (newVal) => {
        if (newVal) {
          showTooltip();
        } else {
          hideTooltip();
        }
      }
    );

    onMounted(() => {
      if (props.visible) {
        showTooltip();
      }
    });

    onUnmounted(() => {
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
.tooltip-wrapper {
  display: inline-block;
  position: relative;
}

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
</style>
