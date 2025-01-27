<template>
    <div>
        <h2>Dashboard Viewer</h2>
        <p v-if="dashboardId">Dashboard ID: {{ dashboardId }}</p>
        <p v-else>No selected dashboard</p>
    </div>
</template>

<script>
export default {
    name: 'DashboardViewer',
    props: {
        dashboardId: {
            type: [String, null],
            default: null
        }
    }
}
</script>