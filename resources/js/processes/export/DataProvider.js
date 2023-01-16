export default {
    getManifest(processId) {
        console.log('here');
        return ProcessMaker.apiClient({
            url: `export/manifest/process/${processId}`,
            method: "GET",
        }).then(response => {
            console.log('data', response.data);
            const rootUuid = response.data.root;
            const assets = response.data.export;
            let d = this.formatAssets(assets, rootUuid);
            console.log('formatted', d);
            return d;
        });
    },
    formatAssets(assets, rootUuid) {
        const groups = {};
        let root = null;
        for (const [uuid, asset] of Object.entries(assets)) {
            const exporter = asset.exporter;
            const type = this.getTypeFromExporter(exporter);

            const info = {
                type: type,
                name: asset.name,
                categories: this.getCategories(asset),
                description: asset.attributes.description || 'N/A',
                createdAt: asset.attributes.created_at || 'N/A',
                updatedAt: asset.attributes.updated_at || 'N/A',
            };

            if (uuid === rootUuid) {
                root = info;
                continue;
            }

            if (!groups[type]) {
                groups[type] = [];
            }

            groups[type].push(info);
        }
        const groupedInfo = Object.entries(groups).map(([key, value]) => {
            return { type: key, items: value };
        });

        return {
            root,
            groups: groupedInfo,
        }
    },
    getTypeFromExporter(exporter) {
        const match = exporter.match(/([^\\]+)Exporter$/);
        return match[1] || 'N/A';
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