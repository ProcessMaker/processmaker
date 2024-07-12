<template>
    <div id="processDescription" class="col-sm-9">
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
      <span
        v-if="readActivated || !largeDescription"
        class="class-version">
        {{ $t('Version') }} {{ processVersion }}
      </span>
      <p
        v-if="!readActivated && largeDescription"
        class="description"
      >
        {{ process.description.slice(0,190) }} ...
        <a
          v-if="!readActivated"
          class="read-more"
          @click="activateReadMore"
        >
        <span style="color: #1572C2;">{{ $t('More') }}</span>
        </a>
      </p>
        <div
          v-if="!readActivated && largeDescription"
          class="class-version">
          {{ $t('Version') }} {{ processVersion }}
        </div>
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
  computed: {
    processVersion() {
      return moment(this.process.updated_at).format();
    },
  },
  mounted() {
    this.verifyDescription();
  },
  methods: {
    /**
     * Verify if the Description is large
     */
    verifyDescription() {
      if (this.process.description.length > 190) {
        this.largeDescription = true;
      }
    },
    activateReadMore() {
      this.readActivated = true;
    },
  },
};
</script>
<style lang="scss" scoped>
@import url("../scss/processes.css");
@import '~styles/variables';

.title {
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
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: left;
  margin-top: 15px;
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
.class-version {
  font-size: 1em;
  color:#B1B8BF;
}
</style>
