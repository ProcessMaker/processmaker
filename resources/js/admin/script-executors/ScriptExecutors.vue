<template>
    <div class="container">
    </div>
</template>

<script>
export default {
    data() {
        return {};
    },
    mounted() {
        ProcessMaker.apiClient.get('/script-executors').then(result => {
        });
    }
}
</script>