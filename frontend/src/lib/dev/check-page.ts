let running = false;

export async function checkPage() {
    if (!import.meta.env.DEV || running) return;

    running = true;

    try {
        const title = document.title.trim();

        const description = document
            .querySelector<HTMLMetaElement>('meta[name="description"]')
            ?.content.trim();

        console.groupCollapsed(`[Page check] ${location.pathname}`);

        try {
            if (!title) {
                console.warn('Page is missing a title.');
            }

            if (!description) {
                console.warn('Page is missing a meta description.');
            }

            const { default: axe } = await import('axe-core');
            const results = await axe.run(document);

            for (const violation of results.violations) {
                console.warn(
                    `[${violation.impact}] ${violation.help}`,
                    {
                        elements: violation.nodes.map((node) => node.target),
                        helpUrl: violation.helpUrl
                    }
                );
            }
        } finally {
            console.groupEnd();
        }
    } catch (error) {
        console.error('Page check failed:', error);
    } finally {
        running = false;
    }
}