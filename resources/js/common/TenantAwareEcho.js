import Echo from "laravel-echo";

export default class TenantAwareEcho {
    constructor(config) {
        const tenantId = document.head.querySelector("meta[name=\"tenant-id\"]")?.content;
        const echoInstance = new Echo(config);

        if (tenantId) {
            const originalPrivate = echoInstance.private.bind(echoInstance);
            echoInstance.private = function (channel, ...args) {
                const prefixedChannel = `tenant_${tenantId}.${channel}`;
                return originalPrivate(prefixedChannel, ...args);
            };

            const originalLeave = echoInstance.leave.bind(echoInstance);
            echoInstance.leave = function (channel, ...args) {
                const prefixedChannel = `tenant_${tenantId}.${channel}`;
                return originalLeave(prefixedChannel, ...args);
            };
        }

        return echoInstance;
    }
}