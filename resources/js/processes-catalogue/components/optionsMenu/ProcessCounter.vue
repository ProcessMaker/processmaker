<template>
  <div class="mt-4">
    <div class="process-counter p-3">
      <span class="process-counter-total"> {{ count }} </span>
      <span class="process-counter-text">{{ $t('Requests of process') }} </span>
    </div>
  </div>
</template>
<script>
  export default {
    props: ["process"],
    data() {
      return {
        count: 0
      }
    },
    mounted() {
      this.fetch();
    },
    methods: {
      fetch() {
        ProcessMaker.apiClient
          .get(`requests/${this.process.id}/count`)
          .then((response) => {
            this.count = response.data.meta.total;
          })
          .catch(() => {
            this.count = 0;
          });
      },
    },
  }
</script>
<style scoped>
.process-counter {
  width: 294px;
  height: 52px;
  border-radius: 8px;
  border: 1px solid #CDDDEE;
  background: #FFF;
}
.process-counter-text {
  color: #556271;
  font-size: 16px;
  font-style: normal;
  font-weight: 400;
  line-height: 24px;
  letter-spacing: -0.32px;
}
.process-counter-total {
  color: #556271;
  font-size: 21px;
  font-style: normal;
  font-weight: 700;
  line-height: 24px;
  letter-spacing: -0.42px;
}
</style>
