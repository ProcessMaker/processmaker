<template>
  <div class="pm-column-filter-op-datetime">
    <b-form-input v-model="input"
                  type="text"
                  placeholder="YYYY-MM-DD"
                  autocomplete="off"
                  readonly
                  size="sm">
    </b-form-input>
    <b-form-datepicker v-model="selectedDate"
                       button-only
                       right
                       size="sm"
                       label-help=""
                       boundary="window"
                       :hide-header="true"
                       button-variant="outline-secondary"
                       class="pm-column-filter-op-button">
    </b-form-datepicker>
    <b-form-timepicker v-model="selectedTime"
                       button-only
                       show-seconds
                       right
                       size="sm"
                       boundary="window"
                       button-variant="outline-secondary"
                       class="pm-column-filter-op-button">
    </b-form-timepicker>
  </div>
</template>

<script>
  export default {
    props: [
      "value"
    ],
    data() {
      return {
        input: "",
        selectedDate: "",
        selectedTime: ""
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = this.convertFromISOString(newValue);
        },
        immediate: true
      },
      input() {
        this.emitInput();
      },
      selectedDate() {
        this.setInput();
      },
      selectedTime() {
        this.setInput();
      }
    },
    mounted() {
      this.selectedDate = this.getCurrentDate(this.input);
      this.selectedTime = this.getCurrentTime(this.input);
    },
    methods: {
      convertToISOString(dateString) {
        let inUTCTimeZone = "";
        if (dateString) {
          inUTCTimeZone = moment(dateString).tz('UTC').toISOString();
        }
        return inUTCTimeZone;
      },
      convertFromISOString(dateString) {
        let inLocalTimeZone = dateString;
        if (dateString) {
          inLocalTimeZone = moment(dateString).tz(window.ProcessMaker.user.timezone).format("YYYY-MM-DD HH:mm:ss");
        }
        return inLocalTimeZone;
      },
      emitInput() {
        this.$emit("input", this.convertToISOString(this.input));
      },
      setInput() {
        this.input = this.selectedDate + " " + this.selectedTime;
      },
      currentDate() {
        let date = new Date();
        return date.toISOString().split("T")[0];
      },
      isDatetime(string) {
        const date = new Date(string);
        return !isNaN(date.getTime()) && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(string);
      },
      getCurrentDate(newValue) {
        if (this.isDatetime(newValue)) {
          let s = newValue.trim().split(" ");
          return s[0];
        } else {
          return this.currentDate();
        }
      },
      getCurrentTime(newValue) {
        if (this.isDatetime(newValue)) {
          let s = newValue.trim().split(" ");
          return s.length > 1 ? s[1] : "00:00:00";
        } else {
          return "00:00:00";
        }
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