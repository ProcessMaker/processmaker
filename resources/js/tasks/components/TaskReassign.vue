<template>
    <span>
        <button class="btn btn-outline-secondary">
            <img class="icon" :src="icon"> Reassign
        </button>
    </span>
</template>

<script>


    export default {
        data() {
            return {
                icon: require('../../../img/reassign.svg'),
            };
        },
    }

</script>

<style lang="scss" scoped>
    .icon {
        width:1.25em;
    }
</style>
