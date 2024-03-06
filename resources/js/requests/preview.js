import Vue from "vue";
import ScreenDetail from "../requests/components/screenDetail";

Vue.component("ScreenDetail", ScreenDetail);

//listen for data from iframe parent
window.addEventListener("data", function(event) {
    if(event.data.type === "tasks-preview") {
        document.getElementById("tasks-preview").innerHTML = event.data.data;
    }
});
