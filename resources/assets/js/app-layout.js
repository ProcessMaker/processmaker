var app = new Vue({
    el: '#app',
    data: {
        sidebarCollapsed: false,
        notificationShown: false,
        notifications: [],
    },
    mounted() {
        /*
          window.Echo.private('ProcessMaker.Model.User.' + window.Processmaker.userId)
              .notification((notification) => {
                  let len = this.notifications.length;
                  this.notifications.push({id: len, html: notification.html});
              });
              */
    }
})

console.log("Attaching sidebar");
new Vue({
    el: '#sidebarMenu',
    data: {
        expanded: false,
        icon: 'img/processmaker-icon-white-sm.png',
        logo: 'img/processmaker-logo-white-sm.png'
    },
    methods: {
        toggleVisibility() {
            this.expanded = !this.expanded;
        }
    }
})

$("#menu-toggle").click(function (e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled");
});
