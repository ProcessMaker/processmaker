<template>
    <div>
        <h2>Process Container</h2>
        <p v-if="processId">Process ID: {{ processId }}</p>
        <p v-else>No selected Process</p>
    </div>
</template>

<script>
export default {
    name: 'ProcessBrowser',
    props: {
        processId: {
            type: [String, Number],
            default: null
        }
    }
}
</script>