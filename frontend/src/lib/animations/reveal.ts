export type RevealOptions = {
    delay?: number;
    className?: string;
};

export function reveal(node: HTMLElement, options: RevealOptions = {}) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const rect = node.getBoundingClientRect();
    const initiallyVisible = rect.top < window.innerHeight && rect.bottom > 0;

    node.style.setProperty('--reveal-delay', `${options.delay ?? 0}ms`);
    const stateClass = options.className ? `reveal-${options.className}` : 'reveal';

    if (reduceMotion || initiallyVisible || !('IntersectionObserver' in window)) {
        node.classList.add(`${stateClass}-visible`);
        return { destroy() {} };
    }

    node.classList.add(`${stateClass}-ready`);
    const observer = new IntersectionObserver(
        ([entry]) => {
            if (!entry.isIntersecting) return;
            node.classList.add(`${stateClass}-visible`);
            observer.unobserve(node);
            observer.disconnect();
        },
        { threshold: 0.08 }
    );

    observer.observe(node);

    return { destroy() { observer.disconnect(); } };
}
