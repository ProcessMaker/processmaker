<template>
    <div v-if="hasVisible">
        <a href="#" @click="titleClick" v-if="hasadd" class="object-menu-item">
            {{title}}
            <i class="plus-icon fas fa-plus-circle"></i>
            <button @click.stop="buttonClick" type="button" class="add-button btn btn-success btn-sm float-right">
                <i class="fas fa-plus fa-sm"></i> Add
            </button>
        </a>
        <a href="#" @click="titleClick" v-else class="object-menu-item">
            {{title}}
        </a>
    </div>
</template>


<script>
    export default {
        props: [
            'title',
            'hasVisible',
            'hasadd'
        ],
        data() {
            return {
            }
        },
        methods: {
            titleClick() {
                // Emit to our parent
                this.$emit('title-click');
            },
            buttonClick() {
                // Emit to our parent
                this.$emit('button-click');
            }
        }
    }
</script>
<style lang="scss" scoped>

    .object-menu-item {
        display: block;
        position: relative;
        height: 22px;
        max-height: 22px;
        line-height: 22px;
        margin-top: 4px;
        color: #333;
        font-weight: 600;
        font-size: 12px;
        padding-left: 4px;
        padding-right: 4px;

        &:hover {
            border-radius: 2px;
            background-color: #e9edf1;

            .add-button {
                display: inline-block;
            }
        }

        .add-button {
            display: none;
            width: 63px;
            height: 22px;
            line-height: 22px;
            font-size: 12px;
            color: white;
            position: absolute;
            right: 0px;
            padding-top: 0px;
            padding-bottom: 0px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .plus-icon {
            color: #788793;
            float: right;
            line-height: 22px;
        }
    }

</style>
