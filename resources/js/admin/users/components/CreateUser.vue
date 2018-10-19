<template>
    <div class="container">
        <h1>Create New User</h1>
        <div class="row">
            <div class="col-8">
                <div class="card card-body">
                    <div class="form-group">
                        <label for="exampleInputEmail1">Username</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
                        <small id="emailHelp" class="form-text text-muted">Username must be distinct</small>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">First Name</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Last Name</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email Address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Password</label>
                        <input type="password" class="form-control" id="exampleInputEmail1"
                               aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Confirm Password</label>
                        <input type="password" class="form-control" id="exampleInputEmail1"
                               aria-describedby="emailHelp">
                    </div>
                    <div id="app" class="form-group">
                        <label>Groups</label>
                        <multiselect
                                v-model="value"
                                :options="options"
                                :multiple="true"
                                track-by="title"
                                :custom-label="customLabel"
                                label="name"
                        >

                            <template slot="tag" slot-scope="props">
                      <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                            <img class="option__image mr-1" :src="props.option.img" alt="Check it">
                            <span class="option__desc mr-1">{{ props.option.title }}
                              <span class="option__title">{{ props.option.desc }}</span>
                            </span>
                        <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)"
                           class="multiselect__tag-icon"></i>
                      </span>
                            </template>

                            <template slot="option" slot-scope="props">
                                <div class="option__desc d-flex align-items-center">
                                    <img class="option__image mr-1" :src="props.option.img" alt="options">
                                    <span class="option__title mr-1">{{ props.option.title }}</span>
                                    <span class="option__small">{{ props.option.desc }}</span>
                                </div>
                            </template>
                        </multiselect>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="btn btn-outline-success" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success ml-2">Save</button>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card card-body">
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore
                    et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                    aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                    cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                    culpa qui officia deserunt mollit anim id est laborum.
                </div>
            </div>
        </div>
    </div>
</template>
0
<script>
    export default {
        data() {
            return {
                value: [],
                options: [
                    {title: 'People', desc: 'HR', img: '/img/avatar-placeholder.gif'},
                    {title: 'Humans', desc: 'HR', img: '/img/avatar-placeholder.gif'},
                    {title: 'Workers', desc: 'HR', img: '/img/avatar-placeholder.gif'}
                ]
            };
        },
        methods: {
            customLabel(option) {
                return ` ${option.img} ${option.title} ${option.desc} `
            },
        }
    }
</script>
<style>
    /* .multiselect__tag {
      background: #788793 !important;
    } */

    .multiselect__element span img {
        border-radius: 50%;
        height: 20px;
    }

    .multiselect__tags-wrap {
        display: flex !important;
    }

    .multiselect__tags-wrap img {
        height: 15px;
        border-radius: 50%;
    }

    .multiselect__tag-icon:after {
        color: white !important;
    }

    /* .multiselect__tag-icon:focus, .multiselect__tag-icon:hover {
       background: #788793 !important;
    } */
    .multiselect__option--highlight {
        background: #00bf9c !important;
    }

    .multiselect__option--selected.multiselect__option--highlight {
        background: #00bf9c !important;
    }

    .multiselect__tags {
        border: 1px solid #b6bfc6 !important;
        border-radius: 0.125em !important;
        height: calc(1.875rem + 2px) !important;
    }

    .multiselect__tag {
        background: #788793 !important;
    }

    .multiselect__tag-icon:after {
        color: white !important;
    }

    /* .multiselect__tag-icon:focus, .multiselect__tag-icon:hover {
       background: gray !important;
   } */
    /* .multiselect__option--highlight {
        background: #00bf9c !important;
    } */
    /* .multiselect__option--selected.multiselect__option--highlight {
        background: #00bf9c !important;
    } */
    /* .multiselect__tags {
        border: 1px solid #b6bfc6 !important;
        border-radius: 0.125em !important;
        height: calc(1.875rem + 2px) !important;
    } */
</style>


