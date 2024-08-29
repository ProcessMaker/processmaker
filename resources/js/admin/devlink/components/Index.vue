<script setup>

const tmpRemote = 'http://processmaker-a.test';
const getRemoteClients = () => {
    const url = tmpRemote + '/oauth/clients';
    window.ProcessMaker.apiClient.get(url, { withCredentials: true }).then((response) => {
        console.log(response.data);
    });
}
</script>

<template>
    <div>
        <button @click="getRemoteClients()">Get Remote Clients</button>
        <iframe src=""
    </div>
</template>