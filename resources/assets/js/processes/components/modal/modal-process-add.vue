<template>
    <b-modal id="createProcess" ref="modal" size="md" @hidden="onHidden" title="Create New Process">
        <form>
            <div class="form-group">
                <label for="title">{{titleLabel}}</label>
                <input id="title" type="text" class="form-control" v-model="title">
            </div>

            <div class="form-group">
                <label for="description">{{descriptionLabel}}</label>
                <textarea id="description" class="form-control" v-model="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="category">{{categoryLabel}}</label>
                <select class="form-control" id="category" v-model="categorySelect">
                    <option v-for="select in categorySelectOptions" :value="select.cat_uid">{{select.cat_name}}</option>
                </select>
            </div>

        </form>

        <template slot="modal-footer">
            <b-button @click="onCancel" class="btn-outline-secondary btn-md">
                CANCEL
            </b-button>
            <b-button @click="onSave" class="btn-secondary text-light btn-md">
                SAVE
            </b-button>
        </template>

    </b-modal>
</template>

<script>
    export default {
        data() {
            return {
                'titleLabel': 'Title',
                'categoryLabel': 'Category',
                'descriptionLabel': 'Description',
                'title': '',
                'description': '',
                'categorySelect': null,
                'categorySelectOptions': []
            }
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onCancel() {
                this.$refs.modal.hide()
            },
            onShow() {
                window.ProcessMaker.apiClient.get('categories')
                    .then((response) => {
                        response.data.unshift({cat_uid: null, cat_name: 'None'});
                        this.categorySelectOptions = response.data;
                        this.title = '';
                        this.description = '';
                        this.categorySelect = null;
                        //display modal create process
                        this.$refs.modal.show()
                    })
                    .catch((error) => {
                        //define how display errors
                    })
            },
            onSave() {
                if (this.cancelToken) {
                    this.cancelToken();
                    this.cancelToken = null;
                }
                const CancelToken = ProcessMaker.apiClient.CancelToken;

                // Load from our api client
                ProcessMaker.apiClient
                    .post(
                        'processes/create',
                        {
                            name: this.title,
                            description: this.description,
                            category_uid: this.categorySelect
                        }
                    )
                    .then(response => {
                        this.$refs.modal.hide();
                        if (response.data && response.data.uid) {
                            //Change way to open the designer
                            window.location.href = '/designer/' + response.data.uid;
                        }
                    })
                    .catch(error => {
                        //define how display errors
                    });
            }
        },
        mounted() {
            this.$refs.modal.hide();
        }
    };
</script>
<style lang="scss" scoped>
    .inline-input {
        margin-right: 6px;
    }

    .inline-button {
        background-color: rgb(109, 124, 136);
        font-weight: 100;
    }

    .input-and-select {
        width: 212px;
    }
</style>
