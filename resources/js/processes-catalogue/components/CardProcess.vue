<template>
  <div class="container processList">
    <b-card
      v-for="process in processList"
      :key="process.id"
      class="card-process"
    >
      <b-card-text>
        <img
          class="icon-process"
          src="/img/default-process.svg"
          :alt="$t('Default Icon')"
        >
        <span class="title-process">{{ process.name }}</span>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
export default {
  props: ["category"],
  data() {
    return {
      processList: [],
    };
  },
  watch: {
    category() {
      this.loadCard();
    },
  },
  mounted() {
    this.loadCard();
  },
  methods: {
    loadCard() {
      /* TODO complete the new API */
      console.log(this.category.name);
      ProcessMaker.apiClient
        .get("processes")
        .then((response) => {
          this.processList = response.data.data;
        });
    },
  },
};
</script>

<style>
.processList {
  display: flex;
  flex-wrap: wrap;
}
.card-process {
  width: 350px;
  height: 240px;
  margin-top: 1rem;
  margin-right: 1rem;
  border-radius: 16px;
}
.card-text {
  display: flex;
  flex-direction: column;
  align-items: baseline;
  padding-top: 15%;
}
.icon-process {
  font-size: 68px;
  margin-bottom: 1rem;
}
.title-process {
  color: #556271;
  font-family: Poppins, sans-serif;
  font-size: 20px;
  font-style: normal;
  font-weight: 700;
  line-height: normal;
  letter-spacing: -0.4px;
  text-transform: uppercase;
}
</style>
