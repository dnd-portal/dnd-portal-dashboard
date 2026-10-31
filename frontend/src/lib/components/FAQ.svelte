<script lang="ts">
    export type FAQItem = {
        question: string;
        answer: string;
    };

    type Props = {
        items: FAQItem[];
    };

    let { items }: Props = $props();
</script>

<div class="faq-list">
    {#each items as item (item.question)}
        <details>
            <summary>
                <span>{item.question}</span>
                <span class="faq-list__indicator" aria-hidden="true"></span>
            </summary>
            <div class="faq-list__answer"><p>{item.answer}</p></div>
        </details>
    {/each}
</div>
