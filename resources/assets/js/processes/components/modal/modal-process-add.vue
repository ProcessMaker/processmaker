<template>
    <b-modal id="createProcess" ref="modal" size="md" @hidden="onHidden" title="Create New Process">
        <form>
            <div class="form-group">
                <label for="title" v-model="title">{{title}}</label>
                <input type="text" class="form-control" v-model="titleValue">
            </div>

            <div class="form-group">
                <label for="description">{{description}}</label>
                <textarea class="form-control" v-model="descriptionValue" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="category" v-model="category">{{category}}</label>
                <select class="form-control" id="category" v-model="categorySelect">
                    <option v-for="(select, index) in categorySelectOptions" :key="index" :value="select.cat_uid">{{select.cat_name}}</option>
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
                // form models here
                'title': 'Title',
                'category': 'Category',
                'titleValue' : '',
                'descriptionValue': '',
                'categorySelect': null,
                'categorySelectOptions': [],
                'description': 'Description',
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
                        response.data.unshift({ cat_uid: null, cat_name: 'None'});
                        this.categorySelectOptions = response.data;
                        //display modal create process
                        this.$refs.modal.show()
                    })
                    .catch((error) => {
                        alert(error);
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
                            name: this.titleValue,
                            description: this.descriptionValue,
                            category_uid: this.categorySelect
                        }
                    )
                    .then(response => {
                        this.data = this.transform(response.data);
                        this.loading = false;
                    })
                    .catch(error => {
                        // Undefined behavior currently, show modal?
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
