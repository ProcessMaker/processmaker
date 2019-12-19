export default {
  data () {
    return {
      socketListeners: []
    };
  },
  mounted () {
    this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${this.instanceId}`, ".ActivityAssigned", (data) => {
      this.$emit("activity-assigned", data);
    });
    this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${this.instanceId}`, ".ProcessCompleted", (data) => {
      this.$emit("process-completed", data);
    });
    this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${this.instanceId}`, ".ProcessUpdated", (data) => {
      this.$emit("process-updated", data);
    });
  },
  methods: {
    addSocketListener (channel, event, callback) {
      this.socketListeners.push({
        channel,
        event
      });
      window.Echo.private(channel).listen(
        event,
        callback
      );
    }
  },
  destroyed () {
    this.socketListeners.forEach((element) => {
      window.Echo.private(element.channel).stopListening(element.event);
    });
  }
};
