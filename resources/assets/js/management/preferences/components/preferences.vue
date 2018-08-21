<template>
  <div class="container">
    <h1 style="margin:15px">Preferences</h1>
    <div class="row">
      <div class="col-4">
        <div class="card card-body">
          <form>
            <form-select label="Time Zone" name="timezone" v-model="timezone"
                        :options="timezoneSelect" helper="Default Time Zone"></form-select>
            <form-select label="Format" name="format" v-model="format"
                        :options="formatSelect" helper="User Name Display Format"></form-select>
            <form-select name="globe" v-model="globe"
                        :options="globalSelect" helper="Global Date Format"></form-select>
            <form-select label="Languages" name="language" v-model="language"
                        :options="langSelect" helper="When the domain is loaded the default app language is "></form-select>
        </form>
      </div>
      </div>
      <div class="col-4">
        <div class="card card-body">
        <h4>Outgoing mail server</h4>
        <form-input v-model="userName" label="User Name"></form-input>
        <form-input v-model="password" label="Password" type="password"></form-input>
        <form-input v-model="hostName" label="Host Name"></form-input>
        <div class="row">
        <form-input class="col-8" v-model="port" label="Server Port"></form-input>
        <form-checkbox class="col-2 mt-4" v-model="ssl" label="SSL/TLS"></form-checkbox>
        </div>
        <form-select label="Authentication" name="authentication" v-model="authentication" :options="authSelect"></form-select>
      </div>
    </div>
    <div class="col-4">
        <div class="card card-body">
          <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
          <p>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco 
          </p>
      </div>
      </div>
  </div>
</div>
</template>

<script>
import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
import FormCheckbox from "@processmaker/vue-form-elements/src/components/FormCheckbox";
import timezone from "timezones.json";

export default {
  components: { FormSelect, FormInput, FormCheckbox },
  data() {
    return {
      timezone: timezone,
      timezoneSelect: [
        { value: "DST", content: "Dateline Standard Time" },
        { value: "U", content: "UTC-11" },
        { value: "HST", content: "Hawaiian Standard Time" },
        { value: "AKDT", content: "Alaskan Standard Time" },
        { value: "PDT", content: "Pacific Daylight Time" },
        { value: "MDT", content: "Mountain Standard Time (Mexico)" },
        { value: "CDT", content: "Central Standard Time" },
        { value: "EDT", content: "Eastern Standard Time" },
        { value: "ADT", content: "Atlantic Standard Time" },
        { value: "MDT", content: "Mid-Atlantic Standard Time" },
        { value: "ADT", content: "Azores Standard Time" },
        { value: "MDT", content: "Morocco Standard Time" },
        { value: "WEDT", content: "W. Europe Standard Time" },
        { value: "GDT", content: "GTB Standard Time" },
        { value: "SAMT", content: "Samara Time" },
        { value: "ADT", content: "Azerbaijan Standard Time" },
        { value: "CAST", content: "Central Asia Standard Time" },
        { value: "SAST", content: "SE Asia Standard Time" },
        { value: "CST", content: "China Standard Time" },
        { value: "NAEST", content: "North Asia East Standard Time" },
        { value: "EAST", content: "E. Australia Standard Time" },
        { value: "CPST", content: "Central Pacific Standard Time" },
        { value: "NZST", content: "New Zealand Standard Time" },
        { value: "KDT", content: "Kamchatka Standard Time" }
      ],
      format: "",
      formatSelect: [{ value: "LASTNAME", content: "@lastname" }],
      globe: "",
      globalSelect: [
        { value: "YY-DD-MM", content: "YY-DD-MM" },
        { value: "MM-DD-YY", content: "MM-DD-YY" },
        { value: "MM-DD-YYYY", content: "MM-DD-YYYY" },
        { value: "YYYY-DD-MM", content: "YYYY-DD-MM" }
      ],
      language: "",
      langSelect: [{ value: "ENGLISH", content: "English" }],
      hostName: "",
      userName: "",
      password: "",
      ssl: false,
      authentication: "",
      authSelect: [
        { value: "SSL", content: "SSL" },
        { value: "GSSAPI", content: "GSSAPI" },
        { value: "NTLM", content: "NTLM" },
        { value: "MD5", content: "MD5" },
        { value: "password", content: "password" }
      ],
      port: ""
    };
  }
};
</script>
