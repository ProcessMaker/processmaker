var app = new Vue({
    el: '#app',
    data: {
        sidebarCollapsed: false,
        notificationShown: false,
        notifications:[],
    },
    mounted() {
      window.Echo.private('ProcessMaker.Model.User.' + window.Processmaker.userId)
        .notification((notification) => {
            let len = this.notifications.length;
            this.notifications.push({id: len, html: notification.html});
        });
      }
})
