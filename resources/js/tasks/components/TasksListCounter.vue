<template>
  <span>
    <span
      v-if="loading"
      class="spinner-border count-spinner"
      role="status"
    >
    </span>
    <span
      v-if="!loading"
      class="badge count-badge"
    >
      {{ count }}
    </span>
  </span>
</template>

<script>
export default {
  props: {
    loading: {
      type: Boolean,
      default() {
        return false;
      }
    },
    count: {
      type: Number,
      default() {
        return null;
      }
    },
  },
};
</script>
<style scoped>
.count-badge {
  background-color: #CDDDEE;
  color: #1572C2;
  border-radius: 100%;
  font-size: 15px;
  margin-right: 5px;
}
.count-spinner {
  height: 1rem;
  width: 1rem;
}
.nav-tabs .nav-item .nav-link:not(.active) .badge {
  background-color: #CDDDEE !important;
}
</style>
