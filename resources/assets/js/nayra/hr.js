/**
 * Test POC of Nayra for Request form
 */

new Vue({
    el: '#start',
    data() {
        return {
            processUid: '',
            event: '',
            start: '',
            end: '',
            reason: ''
        }
    },
    methods: {
        submit() {
            ProcessMaker.apiClient.post('processes/' + this.processUid + '/events/' + this.event + '/trigger', {
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