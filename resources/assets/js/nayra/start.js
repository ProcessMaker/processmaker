/**
 * Test POC of Nayra for example start form
 */

new Vue({
    el: '#start',
    data() {
        return {
            start: '',
            end: '',
            reason: '',
            processUid: '',
            eventUid: ''
        }
    },
    methods: {
        submit() {
            ProcessMaker.apiClient.post('processes/' + this.processUid + '/events/' + this.eventUid + '/trigger', {
                start: this.start,
                end: this.end,
                reason: this.reason
            })
            .then((response) => {
                alert('Received response. Check console');
                console.log(response.data);
            })
        }
    }
})