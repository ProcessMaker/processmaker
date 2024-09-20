<template>
  <div class="tw-space-y-2 tw-items-center">
    <div
      class="tw-flex tw-space-x-1 tw-items-center"
      v-for="(element, index) in inputs"
      :key="index">
      <input
        type="text"
        v-model="inputs[index].value"
        @change="onChange"
        class="tw-px-2 tw-block tw-w-full tw-rounded-md tw-border-0
          tw-py-2 tw-text-gray-900 tw-shadow-sm tw-ring-1 tw-ring-inset
          tw-ring-gray-300 placeholder:tw-text-gray-400 focus:tw-ring-2
          focus:tw-ring-inset focus:tw-ring-indigo-600" />

      <span
        class="tw-h-5 tw-w-6 tw-text-[10px] tw-justify-center
          tw-flex tw-items-center tw-rounded-md hover:tw-bg-gray-300
          tw-border tw-border-color-gray-400">
        <i
          class="fas fa-plus"
          @click.prevent.stop="addInput" />
      </span>

      <span
        class="tw-h-5 tw-w-6 tw-text-[10px] tw-justify-center
        tw-flex tw-items-center tw-rounded-md hover:tw-bg-gray-300
        tw-border tw-border-color-gray-400">
        <i
          class="fas fa-minus"
          @click.prevent.stop="removeInput(index)" />
      </span>
    </div>
  </div>
</template>
<script>
import { defineComponent, computed, ref } from "vue";
export default defineComponent({
  setup(props, {emit}) {
    const inputs = ref([
      {
        id: new Date().getTime(),
        value: null,
      },
    ]);

    const addInput = () => {
      inputs.value.push({
        id: new Date().getTime(),
        value: null,
      });
    };

    const removeInput = (index) => {
      inputs.value.splice(index, 1);
    };

    const onChange = ()=>{
      emit('change',inputs.value)
    }

    return {
      inputs,
      addInput,
      removeInput,
      onChange
    };
  },
});
</script>
