<template>
  <div class="container">
    <h1>Preferences</h1>
    <form>

    </form>
  </div>
</template>

<script>
import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
import FormCheckbox from "@processmaker/vue-form-elements/src/components/FormCheckbox";

export default {
  components: { FormSelect, FormInput, FormCheckbox },
  data() {
    return {
      timezone: {
        title: "Time Zone",
        helper: "Default Time Zone"
      },
      format: {
        title: "Format",
        nameHelper: "User Name Display Format",
        globalHelper: "Global Date Format"
      },
      Languages: {
        title: "Languages",
        helper: "When the domain is loaded the default app language is "
      }
    };
  }
};
</script>
