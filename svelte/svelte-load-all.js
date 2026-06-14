import components from './components.js';

export function SvelteLoadAllComponents() {
    // eager imports already register the custom elements as a side effect
    return Object.keys(components);
}
