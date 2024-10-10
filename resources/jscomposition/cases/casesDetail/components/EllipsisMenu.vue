<template>
  <div class="tw-float-right tw-absolute tw-right-4 tw-top-0">
    <button
      v-for="item in menuOptions"
      v-if="row.screenId"
      :key="item.menu"
      type="button"
      class="tw-rounded-lg tw-bg-white tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-gray-600 tw-shadow-sm
        tw-ring-1 tw-ring-gray-300 hover:tw-bg-gray-50 tw-gap-2"
      @click="executeCallback(item)">
      <i
        class="tw-text-xl tw-text-gray-600"
        :class="item.icon" />
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  row: {
    type: Object,
    default: () => {},
  },
});

const onPrint = () => {
  const { data } = props;
  if (data.screen_version) {
    window.open(`/requests/${props.row.process_request_id}/task/${props.row.id}/screen/${props.row.screen_version}`);
  }
  const msg = "There is not screen";
  ProcessMaker.alert(msg, "danger");
};

const menuOptions = [
  {
    icon: "fa fa-print",
    callback: onPrint,
    name: "print",
  },
];

const executeCallback = (item) => item.callback();

</script>
