export default {
  data () {
    return {
      socketListeners: []
    };
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
