<template>
  <div class="card-deck-flex">
    <slot />
  </div>
</template>

<script>
import CounterCard from "./CounterCard.vue";

export default {
  components: { CounterCard },
  computed: {
    cards() {
      return this.$children.filter((child) => child.$options._componentTag == "counter-card");
    },
  },
  mounted() {
    this.retrieveCounts();
  },
  methods: {
    retrieveCounts() {
      const requests = [];

      this.cards.forEach((card) => {
        requests.push(ProcessMaker.apiClient.get(card.url));
      });

      ProcessMaker.apiClient.all(requests)
        .then(ProcessMaker.apiClient.spread((...responses) => {
          responses.forEach((response) => {
            this.getCard(response.config.url).setCount(response.data.meta.total);
          });
        }))
        .catch((errors) => {
          this.cards.forEach((card) => {
            card.show();
          });
        });
    },
    getCard(url) {
      return this.cards.find((card) => card.url == url);
    },
  },
};
</script>
