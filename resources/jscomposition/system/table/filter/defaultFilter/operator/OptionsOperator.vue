<template>
  <Dropdown
    v-model="selectedOption"
    class="tw-w-full"
    :options="optionsModel"
    @change="onChangeOption" />
</template>
<script setup>
import { ref } from "vue";
import { Dropdown } from "../../../../../base/form";

const props = defineProps({
  value: {
    type: Object,
    default: () => null,
  },
  config: {
    type: Object,
    default: () => null,
  },
});

const emit = defineEmits(["change"]);

const selectedOption = ref(props.value);

const optionsModel = ref(props.config.options || []);

const onChangeOption = () => {
  emit("change", selectedOption.value);
};
</script>
