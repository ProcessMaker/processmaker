<template>
    <div>
        Edit id {{ $route.params.id }}
        
        <router-link :to="{ name: 'index', params: { id: 1 }}">
            Edit 1
        </router-link>
    </div>
</template>