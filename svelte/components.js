const modules = import.meta.glob('./components/**/*.svelte', { eager: true });
const components = {};

for (const path in modules) {
    const componentName = path.split('/').pop().replace('.svelte', '');
    components[componentName] = modules[path];
}

export default components;
