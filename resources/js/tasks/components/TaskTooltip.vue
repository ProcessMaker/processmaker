<template>
    <div :style="{ top: (position.y) + 'px', right: (50 + (position.x)) + 'px' }" class="task-tooltip">
        <slot name="task-tooltip-body">
        </slot>
    </div>
</template>

<script>
export default {
  props: {
    position: {
      type: Object,
      default: () => {},
    },
  },
};
</script>
<style scoped>
.task-tooltip {
  position: absolute;
  padding: 8px;
  border-radius: 4px;
  background-color: #FFFFFF;
  color: #566877;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
}
</style>
