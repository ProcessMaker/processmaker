<template>
  <div class="pm-column-filter-op-datetime">
    <b-form-input v-model="input"
                  type="text"
                  placeholder="YYYY-MM-DD"
                  autocomplete="off"
                  readonly
                  size="sm">
    </b-form-input>
    <b-form-datepicker v-model="input"
                       button-only
                       right
                       size="sm"
                       label-help=""
                       boundary="window"
                       :hide-header="true"
                       button-variant="outline-secondary"
                       class="pm-column-filter-op-button">
    </b-form-datepicker>
  </div>
</template>

<script>
  export default {
    props: [
      "value"
    ],
    data() {
      return {
        input: ""
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = newValue;
          this.dateToDatetime();
        },
        immediate: true
      },
      input() {
        this.emitInput();
      }
    },
    mounted() {
      if (this.input === "") {
        this.input = this.currentDate();
      }
    },
    methods: {
      emitInput() {
        this.dateToDatetime();
        this.$emit("input", this.input);
      },
      dateToDatetime() {
        if (this.input && this.input !== "" && !/\d{2}:\d{2}:\d{2}/.test(this.input)) {
          this.input = this.input + " 00:00:00";
        }
      },
      currentDate() {
        let date = new Date();
        let year = date.getFullYear();
        let month = date.getMonth() + 1;
        let day = date.getDate();
        month = month < 10 ? "0" + month : month;
        day = day < 10 ? "0" + day : day;
        return year + "-" + month + "-" + day;
      }
    }
  };
</script>

<style>
  .pm-column-filter-op-button > button{
    border-color: #ced4da;
    border-left: 0px;
    border-top-left-radius: 0px;
    border-bottom-left-radius: 0px;
    padding: 2px;
  }
</style>
<style scoped>
  .pm-column-filter-op-datetime{
    display: inline-flex;
  }
  .pm-column-filter-op-datetime > input{
    border-top-right-radius: 0px;
    border-bottom-right-radius: 0px;
  }
</style>
