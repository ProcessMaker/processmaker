<template>
  <div
    class="tw-flex tw-items-center tw-justify-start tw-space-x-2 tw-text-gray-500 tw-text-sm">
    <svg
      :class="{
        'hover:tw-cursor-pointer hover:tw-bg-gray-100': pageModel >0
      }"
      class=" tw-rounded-md"
      width="20"
      height="20"
      viewBox="0 0 20 20"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      @click="first">
      <g id="chevron-double-left">
        <path
          id="Shape"
          d="M10 15L5 10L10 5M15 15L10 10L15 5"
          stroke="#9FA8B5"
          stroke-width="1.25"
          stroke-linecap="round"
          stroke-linejoin="round" />
      </g>
    </svg>

    <svg
      :class="{
        'hover:tw-cursor-pointer hover:tw-bg-gray-100 ': pageModel >0
      }"
      class="tw-rounded-md"
      width="20"
      height="20"
      viewBox="0 0 20 20"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      @click="prev">
      <g id="chevron-left">
        <path
          id="Shape"
          d="M12.5 15L7.5 10L12.5 5"
          stroke="#9FA8B5"
          stroke-width="1.25"
          stroke-linecap="round"
          stroke-linejoin="round" />
      </g>
    </svg>

    <div
      class="tw-flex tw-border-1 tw-rounded-md tw-ring-1 tw-ring-inset tw-ring-gray-300">
      <input
        inputmode="numeric"
        pattern="\d*"
        :value="pageModel"
        class="tw-block tw-w-10 tw-text-center tw-flex-1 tw-border-0 tw-bg-transparent tw-pl-1
          focus-visible:tw-outline-none placeholder:tw-text-gray-400"
        placeholder="1"
        @change="$emit('go',parseInt($event.target.value))">
    </div>

    <svg
      :class="{
        'hover:tw-cursor-pointer hover:tw-bg-gray-100': pageModel < pages-1
      }"
      class="tw-rounded-md"
      width="20"
      height="20"
      viewBox="0 0 20 20"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      @click="next">
      <g id="chevron-right">
        <path
          id="Shape"
          d="M7.5 5L12.5 10L7.5 15"
          stroke="#9FA8B5"
          stroke-width="1.25"
          stroke-linecap="round"
          stroke-linejoin="round" />
      </g>
    </svg>

    <svg
      :class="{
        'hover:tw-cursor-pointer hover:tw-bg-gray-100': pageModel < pages-1
      }"
      class="tw-rounded-md"
      width="20"
      height="20"
      viewBox="0 0 20 20"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      @click="last">
      <g id="chevron-double-right">
        <path
          id="Shape"
          d="M10 5L15 10L10 15M5 5L10 10L5 15"
          stroke="#9FA8B5"
          stroke-width="1.25"
          stroke-linecap="round"
          stroke-linejoin="round" />
      </g>
    </svg>

    <div class="tw-px-2">
      <span>{{ `${totalModel} items` }} </span>
    </div>

    <div>
      <Dropdown
        v-model="selectedOption"
        class="tw-w-40"
        :options="options"
        @change="onChangeOption">
        <template #input="{toogleShow}">
          <div
            class="tw-flex tw-full tw-items-center tw-space-x-2"
            @click.prevent.stop="toogleShow()">
            <span>
              {{ `${selectedOption.value} per page` }}
            </span>

            <i
              class=" hover:tw-bg-gray-100 tw-rounded-md hover:tw-cursor-pointer tw-p-1 fas fa-chevron-down" />
          </div>
        </template>
      </Dropdown>
    </div>
  </div>
</template>
<script>
import { defineComponent, ref, computed } from "vue";
import { Dropdown } from "../../base/form";

const optionsPerPage = [
  {
    value: 15,
    label: "15 items",
  },
  {
    value: 30,
    label: "30 items",
  },
  {
    value: 50,
    label: "50 items",

  },
];

export default defineComponent({
  components: {
    Dropdown,
  },
  props: {
    total: {
      type: Number,
      default: () => (0),
    },
    page: {
      type: Number,
      default: () => (0),
    },
    pages: {
      type: Number,
      default: () => (0),
    },
  },
  emits: ["perPage", "go"],
  setup(props, { emit }) {
    const options = ref(optionsPerPage);
    const totalModel = computed(() => props.total);
    const pageModel = ref(props.page);

    const selectedOption = ref({
      value: 15,
      label: "15 items",
    });

    const first = () => {
      if (pageModel.value > 0) {
        pageModel.value = 0;
        emit("go", 0);
      }
    };

    const prev = () => {
      if (pageModel.value > 0) {
        pageModel.value -= 1;
        emit("go", pageModel.value);
      }
    };

    const next = () => {
      if (pageModel.value < props.pages - 1) {
        pageModel.value += 1;
        emit("go", pageModel.value);
      }
    };

    const last = () => {
      if (pageModel.value < props.pages - 1) {
        pageModel.value = props.pages - 1;
        emit("go", pageModel.value);
      }
    };

    const onChangeOption = (e) => {
      selectedOption.value = e;
      emit("perPage", e.value);
    };

    return {
      options,
      selectedOption,
      totalModel,
      pageModel,
      first,
      last,
      next,
      onChangeOption,
      prev,
    };
  },
});
</script>
<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
