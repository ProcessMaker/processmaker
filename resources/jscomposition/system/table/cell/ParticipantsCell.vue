<template>
  <div
    class="tw-overflow-hidden tw-flex -tw-space-x-1 tw-items-center tw-justify-center">
    <div
      v-for="(participant, index) in participants"
      class="tw-group">
      <AppPopover
        position="bottom"
        :hover="true">
        <AppAvatar
          :key="index"
          :class="`${participant.color}`"
          :initials="participant.name[0]"
          class="tw-cursor-pointer">
        </AppAvatar>

        <template #content>
          <div
            class="tw-p-2 tw-rounded-lg tw-border tw-border-gray-300 tw-bg-white">
            {{ participant.name }}
          </div>
        </template>
      </AppPopover>
    </div>

    <span
      v-if="participants.length == 1"
      class="tw-px-2">
      {{ participants[0].name }}
    </span>
  </div>
</template>
<script>
import { defineComponent, computed, ref } from "vue";
import { AppAvatar, AppPopover } from "../../../base";

const colors = [
  "tw-bg-green-500",
  "tw-bg-gray-400",
  "tw-bg-yellow-500",
  "tw-bg-emerald-500",
  "tw-bg-red-500",
  "tw-bg-blue-500",
  "tw-bg-gray-500",
  "tw-bg-purple-500",
];

const getRandomInt = (max) => {
  return Math.floor(Math.random() * max);
};

export default defineComponent({
  components: {
    AppPopover,
    AppAvatar,
  },
  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    column: {
      type: Object,
      default: () => ({}),
    },
    row: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props, { emit }) {
    const participants = computed(() => {
      const response = props.row[props.column.field].map((e) => ({
        ...e,
        color: colors[getRandomInt(colors.length - 1)],
      }));
      return response;
    });

    return {
      participants,
    };
  },
});
</script>
