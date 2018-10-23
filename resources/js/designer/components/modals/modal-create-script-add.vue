<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Custom Script">
    <form>
      <form-input validation="required" :error="errors.title" name="title" v-model="add.title" label="Title"></form-input>
      <form-text-area :error="errors.description" v-model="add.description" label="Description" rows="3"></form-text-area>
      <form-select :options="languageOptions" v-model="add.language" label="Language"></form-select>
    </form>

    <template slot="modal-footer">
      <b-button @click="onCancel" class="btn-outline-secondary btn-md">
        Cancel
      </b-button>
      <b-button @click="onSave(true)" class="btn-secondary text-light btn-md">
        Save And Edit
      </b-button>
    </template>

  </b-modal>
</template>

<script>
import {
  FormInput,
  FormTextArea,
  FormSelect
} from "@processmaker/vue-form-elements/src/components";
export default {
  components: {
    FormInput,
    FormTextArea,
    FormSelect
  },
  props: ["processId"],
  data() {
    return {
      add: {
        title: "",
        description: "",
        language: "php"
      },
      languageOptions: [
        {
          value: "php",
          content: "PHP"
        },
        {
          value: "lua",
          content: "LUA"
        }
      ],
      errors: {
        title: null,
        description: null,
        language: null
      }
    };
  },
  methods: {
    onSave(open) {
      ProcessMaker.apiClient
        .post("process/" + this.processId + "/script", this.add)
        .then(response => {
          ProcessMaker.alert("New Script Successfully Created", "success");
          this.onCancel();
          if (open) {
            //Change way to open the designer
            window.location.href =
              "/processes/" + this.processId + "/script/" + response.data.id;
          }
        })
        .catch(error => {
          //define how display errors
          if (error.response.status === 422) {
            // Validation error
            let fields = Object.keys(error.response.data.errors);
            for (let field of fields) {
              this.errors[field] = error.response.data.errors[field][0];
            }
          }
        });
    },

    onHidden() {
      this.$emit("hidden");
    },
    onCancel() {
      this.$refs.modal.hide();
    }
  },
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  }
};
</script>
<style lang="scss" scoped>
.variable-buttons {
  background-color: rgb(109, 124, 136);
  font-size: 14px;
  font-weight: 300;
}
.bottom-label-form {
  margin-bottom: -6px;
}
.bottom-label {
  font-size: 10px;
}
</style>
