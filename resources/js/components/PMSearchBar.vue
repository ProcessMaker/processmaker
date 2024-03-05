<template>
  <div class="d-flex align-items-start mb-3">
    <div>
      <slot name="left-content"></slot>
    </div>
    <div class="search-bar flex-grow w-100">
      <div class="d-flex align-items-center">
        <i class="fa fa-search ml-3"
           role="button"
           @click="onSearch"></i>
        <textarea type="text"
                  :aria-label="''"
                  :placeholder="$t('Search here')"
                  v-model="input"
                  rows="1"
                  @keydown.enter.prevent
                  class="pmql-input">
        </textarea>
        <i class="fa fa-times pl-1 pr-3"
           role="button"
           @click="onClear"></i>
      </div>
    </div>
    <div>
      <slot name="right-content"></slot>
    </div>
  </div>
</template>

<script>
  export default {
    components: {
    },
    props: {
      value: null
    },
    data() {
      return {
        input: ""
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = newValue;
        },
        immediate: true
      },
      input() {
        this.$emit("input", this.input);
      }
    },
    methods: {
      onSearch(event) {
        this.$emit('onClikSearch', event);
      },
      onClear(event) {
        this.$emit('onClikClear', event);
        this.input = "";
      }
    }
  }
</script>