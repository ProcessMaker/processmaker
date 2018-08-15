<template>
    <div v-if="loaded" class="form-wrap container bg-light mt-3 p-5">
        <h3 class="pl-5">Profile</h3>
        <div class="modal-wrapper">
            <avatar :uid="uid" class="avatar-wrapper">
                <template slot="optional">
                    <img class="profile-overlay" align="center" src="/img/avatar-profile-overlay.png"
                         @click="openModal()">
                </template>
            </avatar>
        </div>
        <modalProfileAvatar ref="profileModal" @image-update="updateImage">
        </modalProfileAvatar>
        <form class="pl-5 pr-5">
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">First Name</label>
                    <input type="text" v-model="data.firstname" class="form-control">
                </div>
                <div class="col">
                    <label for="inputAddress">Last Name</label>
                    <input type="text" v-model="data.lastname" class="form-control">
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">User Name</label>
                    <input type="text" v-model="data.username" class="form-control">
                </div>
                <div class="col">
                    <label for="inputAddress">Email</label>
                    <input type="text" v-model="data.email" class="form-control">
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">New Password</label>
                    <input type="text" class="form-control">
                </div>
                <div class="col">
                    <label for="inputAddress">Change Password</label>
                    <input type="text" class="form-control">
                </div>
            </div>
            <br>
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">Address</label>
                    <input type="text" v-model="data.address" class="form-control" id="inputAddress">
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">City</label>
                    <input type="text" v-model="data.city" class="form-control">
                </div>
                <div class="col">
                    <label for="inputState">State or Region</label>
                    <select id="inputState" v-model="data.state" class="form-control">
                        <option :selected="data.state === null">Choose...</option>
                        <option :key="code" :selected="code == data.state" v-for="(state, code) in states"
                                :value="code">{{state}}
                        </option>
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">Zip Code</label>
                    <input type="text" v-model="data.postal" class="form-control">
                </div>
                <div class="col">
                    <label for="inputState">Country</label>
                    <select id="inputState" v-model="data.country" class="form-control">
                        <option :selected="data.country === null">Choose...</option>
                        <option v-for="country in countries" :key="country.abbreviation" :value="country.abbreviation"
                                :selected="data.country == country.abbreviation">{{country.country}}
                        </option>
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <div class="col">
                    <label for="inputAddress">Phone</label>
                    <input type="text" class="form-control">
                </div>
                <div class="col">
                    <label for="inputTimezone">Default Time Zone</label>
                    <select id="inputTimezone" class="form-control">
                        <option :selected="data.time_zone === null">Choose...</option>
                        <option v-for="(zone, index) in timezones" :key="index" :value="zone.abbr"
                                :selected="data.time_zone == zone.abbr">{{zone.text}}
                        </option>
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-6">
                    <label for="inputState">Language</label>
                    <select id="inputState" class="form-control">
                        <option value="en" :selected="data.lang == 'en'">English</option>
                    </select>
                </div>
            </div>
            <div class="row form-group float-right mt-3">
                <div class="col">
                    <button @click="save" type="button" class="btn btn-secondary text-light">Save</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>

    import VueCroppie from 'vue-croppie';
    import modalProfileAvatar from './modal-profile-avatar.vue'
    import avatar from '../../../components/common/avatar.vue'
    import states from '../../../data/states_hash.json'
    import timezones from 'timezones.json'

    let countries = require('country-json/src/country-by-abbreviation.json')

    export default {
        components: {
            VueCroppie,
            modalProfileAvatar,
            avatar
        },
        data() {
            return {
                loaded: false,
                // Points to a url of the image
                image: '',
                uid: window.ProcessMaker.user.uid,
                data: {},
                states: states,
                timezones: timezones,
                countries: countries
            }
        },
        mounted() {
            this.load()
        },
        methods: {
            // Loads data from our profile api to fetch data and populate fields
            load() {
                ProcessMaker.apiClient.get('admin/profile')
                    .then((response) => {
                        // Copy everything into our data
                        this.data = response.data
                        this.loaded = true;
                    });
            },
            save() {
                delete this.data.avatar;
                ProcessMaker.apiClient.put('admin/profile', this.data)
                    .then((response) => {
                        location.reload();
                        ProcessMaker.alert('Save profile success', 'success');
                    });
            },
            updateImage(newImage) {
                this.image = newImage;
                ProcessMaker.apiClient.put('admin/profile', {
                    avatar: this.image
                })
                    .then((response) => {
                        console.log('image', response)
                    })
            },
            openModal() {
                this.$refs.profileModal.openModal()
            },
            hideModal() {
                this.$refs.modalProfileAvatar.hide()
            },
            onFileChange(e) {
                let files = e.target.files || e.dataTransfer.files;
                if (!files.length)
                    return;
                this.createImage(files[0]);
            }
        }
    }
</script>

<style lang="scss" scoped>
    #browse {
        padding: 0;
        margin-bottom: 0;
    }

    form {
        margin-top: 44px;
    }

    .form-wrap {
        max-width: 620px;
    }

    h3 {
        font-size: 24px;
    }

    .profile-overlay {
        position: absolute;
        top: 0px;
        left: 0px;
    }

    .avatar-wrapper {
        width: 82px;
        height: 82px;
    }

    .modal-wrapper {
        width: 82px;
        margin: auto;
        position: relative;
    }
</style>
