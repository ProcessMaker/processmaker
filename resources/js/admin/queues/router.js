import Vue from 'vue'
import Router from 'vue-router'

import Layout from './layouts/MainLayout.vue'

Vue.use(Router);


let router = new Router({
    mode: 'history',
    base: '/admin/queues',
    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/dashboard',
            component: require('Horizon/pages/Dashboard.vue'),
        },
        {
            path: '/monitoring',
            component: require('Horizon/pages/Monitoring/Index.vue'),
        },
        {
            path: '/monitoring/:tag',
            component: require('Horizon/pages/Monitoring/Tag.vue'),
            children: [
                {
                    path: '/',
                    name: 'monitoring.detail.index',
                    component: require('Horizon/pages/Monitoring/Jobs.vue'),
                    props: {type: 'index'}
                },
                {
                    path: 'failed',
                    name: 'monitoring.detail.failed',
                    component: require('Horizon/pages/Monitoring/Jobs.vue'),
                    props: {type: 'failed'}
                },
            ],
        },
        {
            path: '/metrics',
            component: require('Horizon/pages/Metrics/Index.vue'),
            children: [
                {
                    path: '/',
                    redirect: 'jobs',
                },
                {
                    path: 'jobs',
                    component: require('Horizon/pages/Metrics/Jobs.vue')
                },
                {
                    path: 'queues',
                    component: require('Horizon/pages/Metrics/Queues.vue')
                },
            ],
        },
        {
            path: '/metrics/:type/:slug',
            name: 'metrics.detail',
            component: require('Horizon/pages/Metrics/Metric.vue'),
            props: true,
        },
        {
            path: '/recent-jobs',
            name: 'recent-jobs.detail',
            component: require('Horizon/pages/RecentJobs/Index.vue'),
        },
        {
            path: '/failed',
            component: require('Horizon/pages/Failed/Index.vue'),
        },
        {
            path: '/failed/:jobId',
            name: 'failed.detail',
            component: require('Horizon/pages/Failed/Job.vue'),
            props: true,
        },
    ],
})

for (var index = 0; index < router.options.routes.length; index++) {
    let route = router.options.routes[index];
    if(route.component) {
        if(route.component.components && route.component.components.Layout) {
            // It has a layout, so let's replace it with *our* layout
            route.component.components.Layout = Layout;
        }
    }
}

export default router;