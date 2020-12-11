import Vue from "vue";
import ModelerApp from "./components/ModelerApp";

new Vue({
    render: h => {
        let renderWhenTranslationAvailable = (h) => {
            if(ProcessMaker.i18n.exists('Save') === false) {
                window.setTimeout(() => renderWhenTranslationAvailable(h), 100);
            }
            else {
                return h(ModelerApp);
            }
        };

        return renderWhenTranslationAvailable(h)
}}).$mount("#modeler-app");
