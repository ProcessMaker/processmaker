<template>
  <div
    id="navbarRequestMobile"
    class="d-flex bg-primary p-2 justify-content-start"
  >
  holoiii
    <button
      type="buttom"
      class="dropleft btn btn-primary"
      @click="returnTasks()"
    >
      <i class="fas fa-arrow-left" />
    </button>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  mounted() {
    console.log('aquiiii navabar');
  },
  methods: {
    returnTasks() {
      window.location = `/requests`;
    },
  },
};
</script>
