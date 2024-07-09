<template>
    <div v-if="showPaginationCard" style="width: 100%;">
      <Card v-if="isLastItem"
            :show-cards="false"
            :current-page="currentPage"
            :total-pages="totalPages"
            :card-message="'show-more'"
            :loading="loading"
      />
      <Card v-else
            :show-cards="false"
            :current-page="currentPage"
            :total-pages="totalPages"
            :card-message="cardMessage"
            :loading="loading"
      />
    </div>
  </template>
  
  <script>
  import Card from "./Card.vue";
  
  export default {
    components: { Card },
    props: {
      index: {
        type: Number,
        required: true
      },
      perPage: {
        type: Number,
        required: true
      },
      dataLength: {
        type: Number,
        required: true
      },
      counterPage: {
        type: Number,
        required: true
      },
      totalPages: {
        type: Number,
        required: true
      },
      cardMessage: {
        type: String,
        required: true
      },
      loading: {
        type: Boolean,
        required: true
      }
    },
    computed: {
      showPaginationCard() {
        return (this.index % this.perPage === this.perPage - 1) && this.dataLength >= this.perPage;
      },
      isLastItem() {
        return (this.index + 1) === this.dataLength;
      },
      currentPage() {
        return this.counterPage + Math.floor(this.index / this.perPage);
      }
    }
  };
  </script>
  