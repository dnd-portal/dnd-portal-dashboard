<script lang="ts">let { items=[] }: { items?: {label:string;href?:string}[] } = $props();</script>
<nav aria-label="Breadcrumbs" class="breadcrumbs">{#each items as item, i}{#if item.href}<a href={item.href}>{item.label}</a>{:else}<span>{item.label}</span>{/if}{#if i<items.length-1}<span aria-hidden="true"> / </span>{/if}{/each}</nav>
