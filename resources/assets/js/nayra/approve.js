/**
 * Test POC of Nayra for Request form
 */

new Vue({
    el: '#view',
    data() {
        return {
            processUid: '',
            instanceUid: '',
            tokenUid: '',
            approve: 'no',
        }
    },
    methods: {
        submit() {
            ProcessMaker.apiClient.post('processes/' + this.processUid + '/instances/' + this.instanceUid + '/tokens/' + this.tokenUid + '/complete', {
                approve: this.approve,
            })
            .then((response) => {
                alert('Received response. Check console');
                console.log(response.data);
            })
        }
    }
})