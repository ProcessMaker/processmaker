<template>
  <div>
    <div v-for="(item, index) in input" 
         :key="index"
         class="pm-column-filter-op-in">
      <PMColumnFilterOpDatetime v-model="input[index]"
                                @input="onInput"
                                class="pm-column-filter-op-in-datetime">
      </PMColumnFilterOpDatetime>
      <b-icon icon="plus-square-dotted"
              @click="onPlusIcon(index)"
              class="pm-column-filter-op-in-plus-square">
      </b-icon>
      <b-icon icon="dash-square-dotted"
              @click="onDashIcon(index)"
              class="pm-column-filter-op-in-plus-square">
      </b-icon>
    </div>
  </div>
</template>

<script>
  import PMColumnFilterOpDatetime from "./PMColumnFilterOpDatetime"
  export default {
    components: {
      PMColumnFilterOpDatetime
    },
    props: [
      "value"
    ],
    data() {
      return {
        input: [...this.value]
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = [...newValue];
        },
        immediate: true
      }
    },
    created() {
      this.input = [""];
      this.$emit("input", this.input);
    },
    methods: {
      onPlusIcon(index) {
        this.addInput(index + 1);
      },
      onDashIcon(index) {
        if (this.input.length === 1) {
          return;
        }
        this.removeInput(index);
      },
      addInput(index) {
        this.input.splice(index, 0, "");
        this.$emit("input", this.input);
      },
      removeInput(index) {
        this.input.splice(index, 1);
        this.$emit("input", this.input);
      },
      onInput() {
        this.$emit("input", this.input);
      }
    }
  };
</script>

<style scoped>
  .pm-column-filter-op-in{
    display: inline-flex;
  }
  .pm-column-filter-op-in-plus-square{
    cursor: pointer;
    user-select: none;
  }
  .pm-column-filter-op-in-datetime{
    margin-bottom: 5px;
  }
</style>
