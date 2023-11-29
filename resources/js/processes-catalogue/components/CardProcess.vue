<template>
  <b-row class="processList">
    <b-col
      v-for="process in processList"
      :key="process.id"
      cols="4"
    >
      <b-card>
        {{ process.name }}
      </b-card>
    </b-col>
  </b-row>
</template>

<script>
export default {
  data() {
    return {
      processList: [],
    };
  },
  mounted() {
    this.loadCard();
  },
  methods: {
    loadCard() {
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
  margin-left: 0px;
  margin-right: 0px;
}
</style>
