<script module lang="ts">
	let closeActiveTooltip: (() => void) | undefined;
</script>

<script lang="ts">
	import { onDestroy, onMount, type Snippet } from 'svelte';
	import { fade } from 'svelte/transition';
	import {
		autoUpdate,
		computePosition,
		flip,
		offset,
		shift,
		size
	} from '@floating-ui/dom';

	type Props = {
		text:			string;
		duration?:		number;
		children:		Snippet;
	};

	let {
		text,
		duration = 1500,
		children
	}: Props = $props();

	const tooltipId = $props.id();

	let wrapper:		HTMLDivElement;
	let hideTimer:		ReturnType<typeof setTimeout> | undefined;
	let leaveTimer:		ReturnType<typeof setTimeout> | undefined;

	let opened			= $state(false);
	let focused			= $state(false);
	let reducedMotion	= $state(false);

	function clearTimers() {
		clearTimeout(hideTimer);
		clearTimeout(leaveTimer);

		hideTimer = undefined;
		leaveTimer = undefined;
	}

	function close() {
		clearTimers();
		opened = false;

		if (closeActiveTooltip === close) {
			closeActiveTooltip = undefined;
		}
	}

	function open() {
		if (opened) return;

		closeActiveTooltip?.();

		closeActiveTooltip = close;
		opened = true;
	}

	function startHideTimer() {
		clearTimeout(hideTimer);

		if (focused || duration <= 0) return;

		hideTimer = setTimeout(close, duration);
	}

	function handlePointerEnter(event: PointerEvent) {
		if (event.pointerType !== 'mouse') return;

		clearTimeout(leaveTimer);

		open();
		startHideTimer();
	}

	function handlePointerLeave(event: PointerEvent) {
		if (event.pointerType !== 'mouse') return;

		clearTimeout(leaveTimer);

		// Allow the pointer to cross the gap towards the tooltip.
		leaveTimer = setTimeout(() => {
			if (!focused) close();
		}, 120);
	}

	function handleFocusIn() {
		clearTimers();

		focused = true;
		open();
	}

	function handleFocusOut(event: FocusEvent) {
		if (
			event.relatedTarget instanceof Node &&
			wrapper.contains(event.relatedTarget)
		) {
			return;
		}

		focused = false;
		close();
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape' && opened) {
			close();
		}
	}

	function describeTrigger(node: HTMLDivElement) {
		const trigger = node.firstElementChild;

		if (!(trigger instanceof HTMLElement)) return;

		const descriptions = new Set(
			trigger
				.getAttribute('aria-describedby')
				?.split(/\s+/)
				.filter(Boolean) ?? []
		);

		descriptions.add(tooltipId);

		trigger.setAttribute(
			'aria-describedby',
			[...descriptions].join(' ')
		);

		return {
			destroy() {
				const remaining = (
					trigger.getAttribute('aria-describedby') ?? ''
				)
					.split(/\s+/)
					.filter((id) => id && id !== tooltipId);

				if (remaining.length > 0) {
					trigger.setAttribute(
						'aria-describedby',
						remaining.join(' ')
					);
				} else {
					trigger.removeAttribute('aria-describedby');
				}
			}
		};
	}

	function positionTooltip(node: HTMLDivElement) {
		let disposed = false;
		let latestUpdate = 0;

		node.style.visibility = 'hidden';

		async function updatePosition() {
			const update = ++latestUpdate;

			const { x, y } = await computePosition(wrapper, node, {
				placement: 'top',
				strategy: 'fixed',

				middleware: [
					offset(8),

					flip({
						padding: 12,
						crossAxis: false,
						fallbackPlacements: ['bottom', 'right', 'left']
					}),

					shift({
						padding: 12,
						crossAxis: true
					}),

					size({
						padding: 12,

						apply({ availableWidth, availableHeight, elements }) {
							if (disposed || update !== latestUpdate) return;

							Object.assign(elements.floating.style, {
								maxWidth: `${Math.max(0, availableWidth)}px`,
								maxHeight: `${Math.max(0, availableHeight)}px`
							});
						}
					})
				]
			});

			if (disposed || update !== latestUpdate) return;

			Object.assign(node.style, {
				left: `${x}px`,
				top: `${y}px`,
				visibility: 'visible'
			});
		}

		const cleanup = autoUpdate(wrapper, node, () => {
			void updatePosition().catch((cause: unknown) => {
				console.error('Failed to position tooltip:', cause);
			});
		});

		return {
			destroy() {
				disposed = true;
				cleanup();
			}
		};
	}

	onMount(() => {
		const preference = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		);

		function updatePreference() {
			reducedMotion = preference.matches;
		}

		updatePreference();
		preference.addEventListener('change', updatePreference);

		return () => {
			preference.removeEventListener('change', updatePreference);
		};
	});

	onDestroy(close);
</script>

<svelte:window onkeydown={handleKeydown} />

<div
	bind:this={wrapper}
	use:describeTrigger
	class="tooltip"
	role="group"
	onpointerenter={handlePointerEnter}
	onpointerleave={handlePointerLeave}
	onfocusin={handleFocusIn}
	onfocusout={handleFocusOut}
>
	{@render children()}

	{#if opened}
		<div
			use:positionTooltip
			id={tooltipId}
			class="tooltip__content"
			role="tooltip"
			in:fade={{ duration: reducedMotion ? 0 : 160 }}
			out:fade={{ duration: reducedMotion ? 0 : 100 }}
		>
			{text}
		</div>
	{/if}
</div>