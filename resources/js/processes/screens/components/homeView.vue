<template>
  <div>
    <div>
      <h1>Form Generator</h1>
    </div>
    <div>
      <b-container fluid>
        <b-row class="pb-2">
          <b-col sm="2">
            <label for="textareaForm"> {{ text }}</label>
          </b-col>
          <b-col sm="10">
            <b-form-textarea
              id="textareaForm"
              v-model="textGPT"
              placeholder="I want a form for . . ."
              rows="4"
            >
            </b-form-textarea>
          </b-col>
        </b-row>
        <b-row>
          <b-col>
            <b-button
              sm="4"
              class="custom-btn pb-2"
              variant="outline-success"
              @click="generateForm(textGPT)"
            >
              Genrate Form
            </b-button>
            <b-button
              sm="4"
              class="custom-btn pb-2"
              variant="outline-danger"
              @click="reset"
            >
              Reset
            </b-button>
          </b-col>
        </b-row>
        <b-row>
          <b-col md="8" offset-md="2">
            <TypeView :dataJson="dataJson" />
          </b-col>
        </b-row>
      </b-container>
    </div>
  </div>
</template>

<script>
import mixin from "./mixin/buttonMixin";
import TypeView from "./typeView.vue";

export default {
  name: "home-view",
  mixins: [mixin],
  components: {
    TypeView,
  },
  methods: {
    reset() {
      this.textGPT = "";
      this.dataJson = "";
    },
  },
  data() {
    return {
      text: "Describe your form:",
      textGPT: "",
      dataJson: {
        title: "Leave Absence Form",
        type: "object",
        properties: {
          employee_name: {
            type: "string",
            description: "The name of the employee requesting leave",
          },
          start_date: {
            type: "string",
            format: "date",
            description: "The date the employee wants to start their leave",
          },
          end_date: {
            type: "string",
            format: "date",
            description: "The date the employee wants to end their leave",
          },
          reason: {
            type: "string",
            description: "The reason for the leave request",
          },
          contact_info: {
            type: "object",
            properties: {
              phone: {
                type: "string",
                description: "The employee's phone number",
              },
              email: {
                type: "string",
                format: "email",
                description: "The employee's email address",
              },
            },
          },
        },
        required: [
          "employee_name",
          "start_date",
          "end_date",
          "reason",
          "contact_info",
        ],
      },
    };
  },
};
</script>
<style scoped>
.custom-btn {
  width: 150px;
  margin-left: 50px;
}
</style>
