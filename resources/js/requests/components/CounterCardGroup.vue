<template>
  <div class="card-deck-flex">
    <slot></slot>
  </div>
</template>

<script>
  import CounterCard from "./CounterCard";

  export default {
    components: { CounterCard },
    mounted() {
      this.retrieveCounts();
    },
    computed: {
      cards() {
        return this.$children.filter(child => {
          return child.$options._componentTag == 'counter-card';
        });
      },
    },
    methods: {
      retrieveCounts() {
        let requests = [];
        
        this.cards.forEach(card => {
          requests.push(ProcessMaker.apiClient.get(card.url));
        });
        
        ProcessMaker.apiClient.all(requests)
          .then(ProcessMaker.apiClient.spread((...responses) => {
            responses.forEach(response => {
              this.getCard(response.config.url).setCount(response.data.meta.total);
            });
          }))
          .catch(errors => {
            this.cards.forEach(card => {
              card.show();
            });
          });
      },
      getCard(url) {
        return this.cards.find(card => {
          return card.url == url;
        });
      },
    }
  }
</script>
