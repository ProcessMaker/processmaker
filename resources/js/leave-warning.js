import Vue from "vue";
new Vue({
    data() {
        return {
            isTimedOut: false
        }
    },
    created() {
        window.addEventListener('beforeunload', this.handler);
        if (window.ProcessMaker && window.ProcessMaker.AccountTimeoutWorker) {
            window.ProcessMaker.AccountTimeoutWorker.addEventListener('message', (e) => {
                if (e.data.method === 'timedOut') {
                    this.isTimedOut = true;
                }
            });
        }

    },
    methods: {
        handler(event) {
            if (this.isTimedOut) {
                event.preventDefault();
                return;
            }
            let confirmationMessage = __('Are you sure you want to leave?');
            event.returnValue = confirmationMessage;     // Gecko, Trident, Chrome 34+
            return confirmationMessage;
        }
    }
});