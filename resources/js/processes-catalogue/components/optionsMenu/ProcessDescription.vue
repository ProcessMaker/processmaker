<template>
    <div class="col-sm-9">
      <span class="title">
        {{ $t('Details') }}
      </span>
      <p class="title-process">
        {{ process.name }}
      </p>
      <p
        v-if="readActivated || !largeDescription"
        class="description"
      >
        {{ process.description }}
      </p>
      <p
        v-if="!readActivated && largeDescription"
        class="description"
      >
        {{ process.description.slice(0,300) }}
        <a
          v-if="!readActivated"
          class="read-more"
          @click="activateReadMore"
        >
          ...
        </a>
      </p>
    </div>
</template>
<script>
export default {
  props: ["process"],
  data() {
    return {
      readActivated: false,
      largeDescription: false,
    };
  },
  methods: {
    activateReadMore() {
      this.readActivated = true;
    },
  },
};
</script>
<style scoped>
.title{
  color: #1572C2;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.02em;
}
.title-process {
  color: #4C545C;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: -0.02em;
  text-align: left;
}
.description {
  color: #4f606d;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: left;
}
</style>
