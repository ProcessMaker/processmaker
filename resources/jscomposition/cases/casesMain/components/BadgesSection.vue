<template>
  <div class="tw-flex tw-justify-between">
    <transition-group
      name="badge"
      tag="div"
      class="tw-flex tw-space-x-1">
      <Badge
        v-for="(item, index) in data"
        :id="`badge-${item.fieldName.replace(/\s+/g, '-')}`"
        :key="item.fieldName"
        class="tw-text-xs tw-bg-gray-100"
        color="gray">
        <span>
          <span class=" tw-font-bold"> {{ item.fieldName }} </span>
          <span> {{ item.operator }} </span>
          <span> {{ item.value }} </span>

          <i
            class="fas fa-times tw-pl-1 hover:tw-cursor-pointer"
            @click="onClose(item, index)" />
        </span>
      </Badge>
    </transition-group>
    <slot
      v-if="data.length"
      name="endsection" />
  </div>
</template>
<script setup>
import { computed } from "vue";
import { Badge } from "../../../base/ui/index";

const emit = defineEmits(["remove"]);
const props = defineProps({
  value: {
    type: Array,
    default: () => [],
  },
});

const data = computed(() => props.value);

const onClose = (item, index) => {
  emit("remove", item, index);
};
</script>
<style scoped>
.badge-enter-active, .badge-leave-active {
  transition: all 0.5s ease;
}

.badge-enter, .badge-leave-to /* .badge-leave-active in <=2.1.8 */ {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
