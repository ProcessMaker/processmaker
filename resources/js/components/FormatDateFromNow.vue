<template>
  <span>
    {{ formatDate({ date }) }}
  </span>
</template>

<script>
import { formatDistanceToNow } from "date-fns";

export default {
  props: ["date"],
  methods: {
    formatDate(date) {
        //Date structure we're following is an object with a date inside of it
        return formatDistanceToNow(new Date(date.date), { addSuffix: true });
    },
  },
};
</script>