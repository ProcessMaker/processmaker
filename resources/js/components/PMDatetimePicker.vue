<template>
  <b-input-group class="pm-datetime-picker">
    <b-form-input v-model="input"
                  type="text"
                  autocomplete="off"
                  readonly
                  :placeholder="placeholder"
                  :size="size"
                  class="pm-datetime-picker-input">
    </b-form-input>
    <b-input-group-append>
      <b-form-datepicker v-model="selectedDate"
                         button-only
                         right
                         :size="size"
                         boundary="window"
                         button-variant="light"
                         class="pm-datetime-picker-button-datetime"
                         :class="{'pm-datetime-picker-button-border-right': !withTime}"
                         label-help=""
                         :hide-header="true">
        <template v-slot:button-content
                  v-if="$slots['button-content-datepicker']">
          <slot name="button-content-datepicker"></slot>
        </template>
      </b-form-datepicker>
    </b-input-group-append>
    <b-input-group-append v-if="withTime">
      <b-form-timepicker v-model="selectedTime"
                         button-only
                         right
                         :size="size"
                         boundary="window"
                         button-variant="light"
                         class="pm-datetime-picker-button-datetime"
                         :class="{'pm-datetime-picker-button-border-right': withTime}"
                         show-seconds>
        <template v-slot:button-content
                  v-if="$slots['button-content-timepicker']">
          <slot name="button-content-timepicker"></slot>
        </template>
      </b-form-timepicker>
    </b-input-group-append>
  </b-input-group>
</template>

<script>
  export default {
    props: {
      value: {
        type: null,
        default: ""
      },
      placeholder: {
        type: null,
        default: "YYYY-MM-DD"
      },
      size: {
        type: null,
        default: ""
      },
      withTime: {
        type: null,
        default: true
      },
      currentDatetime: {
        type: null,
        default: true
      },
      format: {
        type: null,
        default: "YYYY-MM-DD HH:mm:ss"
      }
    },
    data() {
      return {
        input: "",
        selectedDate: "",
        selectedTime: "",
        timeZone: window.ProcessMaker.user.timezone,
        guestTimeZone: moment.tz.guess()
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
      this.selectedDate = this.getValueFromFormat(this.input, "YYYY-MM-DD");
      this.selectedTime = this.getValueFromFormat(this.input, "HH:mm:ss", "00:00:00");
    },
    methods: {
      emitInput() {
        this.$emit("input", this.convertToISOString(this.input));
      },
      setInput() {
        let datetime = this.selectedDate + " " + this.selectedTime;
        this.input = moment(datetime).tz(this.guestTimeZone).format(this.format);
      },
      getValueFromFormat(string, format, defaultValue) {
        if (string === "") {
          return string;
        }
        if (this.isDatetime(string)) {
          return moment(string).tz(this.guestTimeZone).format(format);
        } else {
          if (defaultValue !== undefined) {
            return defaultValue;
          }
          return moment().tz(this.guestTimeZone).format(format);
        }
      },
      convertFromISOString(dateString) {
        if (!this.isDatetime(dateString)) {
          return dateString;
        }
        return moment(dateString).tz(this.timeZone).format(this.format);
      },
      convertToISOString(dateString) {
        if (!this.isDatetime(dateString)) {
          return dateString;
        }
        return moment(dateString).tz("UTC").toISOString();
      },
      isDatetime(string) {
        if (!string) {
          return false;
        }
        let date = new Date(string);
        return !isNaN(date) && isFinite(date);
      }
    }
  };
</script>

<style>
  .pm-datetime-picker-button-datetime > button {
    display: flex;
    justify-content: center;
    align-items: center;
    border-top: 1px solid #b6bfc6;
    border-bottom: 1px solid #b6bfc6;
    border-left: 0px;
  }
  .pm-datetime-picker-button-border-right > button {
    border-right: 1px solid #b6bfc6;
    border-top-right-radius: 2px !important;
    border-bottom-right-radius: 2px !important;
  }
</style>
<style scoped>
  .pm-datetime-picker {
    display: inline-flex;
  }
  .pm-datetime-picker-input {
    background-color: white;
  }
</style>