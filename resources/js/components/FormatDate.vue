<template>
  <div>
    {{ formatDate(date) }}
  </div>
</template>

<script>
import { format } from "date-fns";

export default {
  props: ['date'],
  methods: {
    formatDate(date) {
      //   console.log('here');
      //   var date = new Date();
      //   console.log(today);
      format(date, "yyyy-MM-dd HH:mm");
      console.log(format(date, "yyyy-MM-dd HH:mm"));
    },
  },
};
</script>