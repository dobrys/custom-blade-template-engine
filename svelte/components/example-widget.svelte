<svelte:options customElement="example-widget" />

<script>
  let { message = 'Hello from Svelte!' } = $props();
</script>

<div style="border:1px solid #ccc; padding:8px;">{message}</div>
