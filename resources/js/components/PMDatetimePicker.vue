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
      <template v-slot:button-content>
        <svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M0.375 6H13.625C13.8313 6 14 6.16875 14 6.375V14.5C14 15.3281 13.3281 16 12.5 16H1.5C0.671875 16 0 15.3281 0 14.5V6.375C0 6.16875 0.16875 6 0.375 6ZM14 4.625V3.5C14 2.67188 13.3281 2 12.5 2H11V0.375C11 0.16875 10.8313 0 10.625 0H9.375C9.16875 0 9 0.16875 9 0.375V2H5V0.375C5 0.16875 4.83125 0 4.625 0H3.375C3.16875 0 3 0.16875 3 0.375V2H1.5C0.671875 2 0 2.67188 0 3.5V4.625C0 4.83125 0.16875 5 0.375 5H13.625C13.8313 5 14 4.83125 14 4.625Z" fill="#6A7888"/>
        </svg>
      </template>
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
      emitInput() {
        this.$emit("input", this.convertToISOString(this.input));
      },
      setInput() {
        this.input = this.selectedDate + " " + this.selectedTime;
      },

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
      },

      isDatetime(string) {
        const date = new Date(string);
        return !isNaN(date.getTime()) && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(string);
      },
      currentDate() {
        let date = new Date();
        return date.toISOString().split("T")[0];
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