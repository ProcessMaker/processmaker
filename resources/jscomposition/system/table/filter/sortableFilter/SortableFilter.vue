<template>
  <div
    :class="{
      'tw-text-blue-500': order
    }"
    class="hover:tw-text-blue-600 tw-cursor-pointer">
    <i
      v-if="!order"
      class="fas fa-sort"
      @click.prevent="onChange('asc')" />

    <i
      v-if="order == 'asc'"
      class="fas fa-sort-up"
      @click.prevent="onChange('desc')" />

    <i
      v-if="order == 'desc'"
      class="fas fa-sort-down"
      @click.prevent="onChange('asc')" />
  </div>
</template>

<script setup>
import { ref } from "vue";

const emit = defineEmits(["change"]);

const props = defineProps({
  state: {
    type: String,
    default: () => (""),
  },
});

const order = ref(props.state);

const onChange = (val) => {
  order.value = val;
  emit("change", val);
};

</script>
