/**
 * Process start components
 */
import ProcessStart from "./components/process-start.vue";
import ProcessCall from "./components/process-call.vue";

// Vacation request form components:
import RequestForm from "./components/request-form.vue";
import ApproveForm from "./components/approve-form.vue";
import ValidateForm from "./components/validate-form.vue";

// Boot up our vue instance
new Vue({
    el: "#start",
    components: {
        ProcessStart,
        ProcessCall,
        RequestForm,
        ApproveForm,
        ValidateForm
    }
});
