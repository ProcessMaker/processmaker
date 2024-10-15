<template>
  <div
    class="tw-w-auto tw-flex tw-flex-col sm:tw-flex-row tw-space-y-2 sm:tw-space-y-0 sm:tw-space-x-4 sm:tw-py-0">
    <ThreeSectionCard
      v-for="(counter, index) in data"
      :key="index"
      :header="counter.header"
      :body="counter.body"
      :active="counter.active"
      class="tw-w-full"
      :color="counter.color"
      :icon="counter.icon"
      @click="onClick(counter, index)" />
  </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from "vue";
import { ThreeSectionCard } from "../../../base/index";

export default defineComponent({
  components: { ThreeSectionCard },
  props: {
    value: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["input", "change", "update:value"],
  setup(props, { emit }) {
    const data = computed({
      get() {
        return props.value;
      },
      set(dt) {
        emit("input", dt);
      },
    });

    const onClick = (counter, idxCounter) => {
      const dt = data.value.map((x, index) => {
        x.active = false;

        if (index == idxCounter) {
          x.active = true;
        }
        return x;
      });

      data.value = dt;

      emit("change", counter, idxCounter);
    };

    onMounted(() => {});

    return {
      data,
      onClick,
    };
  },
});
</script>
