export default {
    getManifest(processId) {
        console.log('here');
        return ProcessMaker.apiClient({
            url: `export/manifest/process/${processId}`,
            method: "GET",
        }).then(response => {
            console.log('data', response.data);
            const assets = response.data.export;
            return this.formatAssets(assets);
        });
    },
    formatAssets(assets) {
        const groups = {};
        for (const [uuid, asset] of Object.entries(assets)) {
            const exporter = asset.exporter;
            if (!groups[exporter]) {
                groups[exporter] = [];
            }
            groups[exporter].push({
                type: this.getTypeFromExporter(exporter),
                name: asset.name,
                categories: this.getCategories(asset),
                description: asset.attributes.description || 'N/A',
                createdAt: asset.attributes.created_at || 'N/A',
                udpatedAt: asset.attributes.updated_at || 'N/A',
            });
        }
    },
    getTypeFromExporter(exporter) {
        console.log('exporter', exporter.match(/([^\\]+)Exporter$/));
        return 'OK';
    },
    getCategories(asset, allAssets) {
        const categories = asset.dependents.filter(d => d.type === 'categories').map(category => {
            return allAssets[category.uuid].name;
        });
        if (categories.length === 0) {
            categories.push('Uncategorized');
        }
        return categories.join(', ');
    }
}