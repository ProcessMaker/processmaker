<template>
  <span>
    {{ formatDate({ date }) }}
  </span>
</template>

<script>
import { format } from "date-fns";
import { timezone_format } from "../timezone.js";

export default {
  props: ["date"],
  methods: {
    formatDate(date) {
        //Date structure we're following is an object with a date inside of it
        return format(new Date(date.date), timezone_format());
    },
  },
};
</script>