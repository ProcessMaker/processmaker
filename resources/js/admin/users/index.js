import Vue from 'vue';
import VuePassword from "vue-password";
import UsersListing from './components/UsersListing';

Vue.component("vue-password", VuePassword);

new Vue({
    el: '#users-listing',
    components: {UsersListing},
    data: {
        filter: '',
        addUser: {
            username: '',	
            firstname: '',	
            lastname: '',	
            title: '',	
            status: '',	
            email: '',	
            password: '',	
            confpassword: '',	
            addError: {},		
        }
    },
    methods: {
        reload() {
            this.$refs.listing.dataManager([{
                field: 'updated_at',
                direction: 'desc'
            }]);
        },
        validatePassword() {	
            if (this.addUser.password.trim().length > 0 && this.addUser.password.trim().length < 8) {	
              this.addUser.addError.password = ['Password must be at least 8 characters']	
              this.$refs.passwordStrength.updatePassword('')	
              this.addUser.password = ''	
              this.addUser.confpassword = ''	
              return false	
            }	
            if (this.addUser.password !== this.addUser.confpassword) {	
              this.addUser.addError.password = ['Passwords must match']	
              this.$refs.passwordStrength.updatePassword('')	
              this.addUser.password = ''	
              this.addUser.confpassword = ''		
              return false	
            }	
            return true	
        },
        onClose() {	
            this.addUser.username = '';	
            this.addUser.firstname = '';	
            this.addUser.lastname = '';	
            this.addUser.status = '';	
            this.addUser.title = '',	
            this.addUser.email = '';	
            this.addUser.password = '';	
            this.addUser.confpassword = '';	
            this.addUser.addError = {};	
        },
        showMsgBox(message, restoreData) {
            this.$bvModal.msgBoxConfirm([message], {
                title: this.$t('Deleted User Found'),
                centered: true,
                okVariant: 'secondary',
                okTitle: this.$t('Save'),
                cancelVariant: 'outline-secondary'
            }).then(value => {
                if (value) {
                    ProcessMaker.apiClient.put('/users/restore', restoreData).then(response => {
                        ProcessMaker.alert(this.$t("The user was restored."), "success");
                        this.reload();
                    }).catch(error => {
                        ProcessMaker.alert(error, "danger");
                    });
                } else {
                    this.$refs.addUser.show();
                }
            }).catch(err => {
                ProcessMaker.alert(err, "danger");
            });
        },	
        onSubmit(bvModalEvt) {	
            bvModalEvt.preventDefault();
            if (this.validatePassword()) {	
              ProcessMaker.apiClient.post("/users", {	
                username: this.addUser.username,	
                firstname: this.addUser.firstname,	
                lastname: this.addUser.lastname,	
                title: this.addUser.title,	
                status: this.addUser.status,	
                email: this.addUser.email,	
                password: this.addUser.password	
              }).then(function (response) {
                window.location = "/admin/users/" + response.data.id + '/edit?created=true';
              }).catch(error => {
                this.addUser.addError = error.response.data.errors;	
                if (!Object.values(this.addUser.addError).some(field => field.includes('userExists') || field.includes('The email field is required.'))) {
                    if (this.addUser.addError.email) {
                        let h = this.$createElement;
                        let messageVNode = h('p', {}, [
                            this.$t('An existing user has been found with the email')
                            + ' "' + this.addUser.email + '" ' + 
                            this.$t('would you like to save and reactivate their account?')
                        ]);
                        let restoreData = {
                            email: this.addUser.email
                        };
                        this.showMsgBox(messageVNode, restoreData);
                    } else if (this.addUser.addError.username) {
                        let h = this.$createElement;
                        let messageVNode = h('p', {}, [
                            this.$t('An existing user has been found with the username')
                            + ' "' + this.addUser.username + '" ' + 
                            this.$t('would you like to save and reactivate their account?')
                        ]);
                        let restoreData = {
                            username: this.addUser.username
                        };
                        this.showMsgBox(messageVNode, restoreData);
                    }
                    this.$refs.addUser.hide();
                }
              });	
            }	
        },		
    }
});
