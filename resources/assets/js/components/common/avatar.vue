<template>
    <div class="avatar">
        <div v-if="!image" class="profile-avatar-none text-light" :style="{backgroundColor: backgroundColor}">
            {{label}}
        </div>
        <img v-else :src="image">
        <img class="profile-overlay" align="center" src="/img/avatar-overlay-blank.png" @click="openModal()">
        <slot name="optional">

        </slot>
    </div>
</template>

<script>
    export default {
        props: [
            'background',
            'uid'
        ],
        computed: {
            backgroundColor() {
                // We could randomize based on label or something
                return this.background;
            }
        },
        methods: {
            setImage(image) {
                this.image = image;
            },
            fetch() {
                console.log('fetch');
                // Fetch url
                // If 404, we'll have a json response with error and user meta data
                // If 200, then it's actually a valid image url, so let's replace our image
                // with the data url
                ProcessMaker.apiClient('admin/profile')
                    .then((response) => {
                        this.label = (response.data.firstname[0] + response.data.lastname[0]).toUpperCase();
                        if (response.data.avatar) {
                            this.image = response.data.avatar;
                        }
                    })
                    .catch((error) => {
                        if (error.response.status == 404 && error.response.data.user) {
                            // We have a 404 and we have a user object returned, let's fetch label
                            let user = error.response.data.user;
                            this.label = (user.firstname[0] + user.lastname[0]).toUpperCase();
                        } else {
                            this.label = 'n/a'
                        }
                    })
            }
        },
        mounted() {
            this.fetch();
        },
        data() {
            return {
                // Default to no image
                image: null,
                label: ''
            }
        }

    }
</script>

<style lang="scss" scoped>
    .avatar {
        display: inline-block;
        border-radius: 50%;
        position: relative;
    }

    img {
        max-width: 100%;
    }

    .profile-avatar-none {
        max-width: 100%;
        min-width: 100%;
        min-height: 100%;
        max-height: 100%;
        background-color: rgb(251, 181, 4);
        text-align: center;
        font-size: 40px;
        font-weight: bold;
    }

    .profile-avatar {
        max-width: 100%;
    }

    .profile-overlay {
        position: absolute;
        top: 0px;
        max-width: 100%;
        max-height: 100%;
        min-width: 100%;
        min-height: 100%;
    }

</style>
