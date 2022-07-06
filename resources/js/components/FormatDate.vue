<template>
  <span>
    {{ formatDate() }}
  </span>
</template>

<script>
import { format } from "date-fns";
import { timezone_format } from "../timezone.js";

export default {
  methods: {
    formatDate() {
        const date = new Date();
        return format(date, timezone_format());
    },
  },
};
</script>