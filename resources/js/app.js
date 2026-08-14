import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import phpTemplate from 'highlight.js/lib/languages/php-template';
import bash from 'highlight.js/lib/languages/bash';
import json from 'highlight.js/lib/languages/json';
import sql from 'highlight.js/lib/languages/sql';
import javascript from 'highlight.js/lib/languages/javascript';
import ini from 'highlight.js/lib/languages/ini';
import plaintext from 'highlight.js/lib/languages/plaintext';

hljs.registerLanguage('php', php);
hljs.registerLanguage('php-template', phpTemplate);
hljs.registerLanguage('blade', phpTemplate);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('shell', bash);
hljs.registerLanguage('json', json);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('env', ini);
hljs.registerLanguage('ini', ini);
hljs.registerLanguage('text', plaintext);

const escapeHtml = (value) =>
    String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

/* -------------------------------------------------------------------------
 | Code blocks: highlight + copy button
 * ---------------------------------------------------------------------- */

function decorateCodeBlocks(root = document) {
    root.querySelectorAll('pre > code').forEach((block) => {
        if (block.dataset.decorated) return;
        block.dataset.decorated = '1';

        hljs.highlightElement(block);

        const pre = block.parentElement;
        pre.classList.add('group', 'relative');

        const button = document.createElement('button');
        button.type = 'button';
        button.className =
            'absolute right-2 top-2 rounded-md border border-ink-700 bg-ink-800/80 px-2 py-1 text-[11px] font-medium text-ink-300 opacity-0 transition group-hover:opacity-100 focus:opacity-100 hover:text-white';
        button.textContent = 'Copy';

        button.addEventListener('click', async () => {
            await navigator.clipboard.writeText(block.textContent);
            button.textContent = 'Copied';
            setTimeout(() => (button.textContent = 'Copy'), 1500);
        });

        pre.appendChild(button);
    });
}

/* -------------------------------------------------------------------------
 | Lesson table of contents: highlight the section you are reading
 * ---------------------------------------------------------------------- */

function trackReadingPosition() {
    const links = document.querySelectorAll('[data-toc-link]');
    if (!links.length) return;

    const headings = [...document.querySelectorAll('.lesson h2[id]')];
    if (!headings.length) return;

    const activate = (id) => {
        links.forEach((link) => {
            const active = link.getAttribute('href') === `#${id}`;
            link.classList.toggle('text-indigo-600', active);
            link.classList.toggle('font-semibold', active);
            link.classList.toggle('text-ink-500', !active);
        });
    };

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
            if (visible) activate(visible.target.id);
        },
        { rootMargin: '-80px 0px -70% 0px', threshold: 0 },
    );

    headings.forEach((heading) => observer.observe(heading));
}

/* -------------------------------------------------------------------------
 | Playground
 * ---------------------------------------------------------------------- */

const renderers = {
    chat(data) {
        if (data.refused) {
            return `<div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">The safety classifiers declined this request (<code>stop_reason: refusal</code>). The content came back empty — this is why you check <code>stop_reason</code> before reading the response.</div>`;
        }
        return `${answerBlock(data.text)}${usageBlock(data.usage)}`;
    },

    extract(data) {
        return `
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Validated JSON — ready for <code>Ticket::create()</code></p>
            <pre class="!mt-0"><code class="language-json">${escapeHtml(JSON.stringify(data.json, null, 2))}</code></pre>
            ${usageBlock(data.usage)}`;
    },

    tools(data) {
        const trace = (data.trace || [])
            .map(
                (step, i) => `
                <li class="rounded-lg border border-ink-200 bg-white p-3">
                    <p class="text-xs font-semibold text-indigo-600">Step ${i + 1} — Claude called <code>${escapeHtml(step.tool)}()</code></p>
                    <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-ink-400">Arguments it chose</p>
                    <pre class="!mt-1 !text-[12px]"><code class="language-json">${escapeHtml(JSON.stringify(step.input, null, 2))}</code></pre>
                    <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-ink-400">What your database returned</p>
                    <pre class="!mt-1 !text-[12px]"><code class="language-json">${escapeHtml(JSON.stringify(step.output, null, 2))}</code></pre>
                </li>`,
            )
            .join('');

        const totalCost = (data.usage || []).reduce((sum, u) => sum + (u.cost || 0), 0);

        return `
            ${answerBlock(data.text)}
            ${
                trace
                    ? `<p class="mt-5 mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Tool trace (${data.turns} model turns)</p><ol class="space-y-2">${trace}</ol>`
                    : '<p class="mt-4 text-sm text-ink-500">Claude answered without calling a tool.</p>'
            }
            <p class="mt-4 text-xs text-ink-500">${(data.usage || []).length} API call(s), about $${totalCost.toFixed(6)} total.</p>`;
    },

    rag(data) {
        const chunks = (data.chunks || [])
            .map(
                (chunk, i) => `
                <li class="rounded-lg border border-ink-200 bg-white p-3">
                    <p class="text-xs font-semibold text-indigo-600">[${i}] ${escapeHtml(chunk.title)} · similarity ${chunk.score}</p>
                    <p class="mt-1 whitespace-pre-wrap text-[13px] leading-relaxed text-ink-600">${escapeHtml(chunk.text)}</p>
                </li>`,
            )
            .join('');

        return `
            ${answerBlock(data.text)}
            <p class="mt-5 mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">
                Retrieved ${(data.chunks || []).length} of ${data.total_chunks} chunks — this is all Claude was allowed to see
            </p>
            <ol class="space-y-2">${chunks || '<li class="text-sm text-ink-500">Nothing matched.</li>'}</ol>
            ${usageBlock(data.usage)}`;
    },
};

function answerBlock(text) {
    return `<div class="whitespace-pre-wrap rounded-lg border border-ink-200 bg-white p-4 text-[15px] leading-relaxed text-ink-800">${escapeHtml(text || '(empty response)')}</div>`;
}

function usageBlock(usage) {
    if (!usage) return '';
    return `
        <dl class="mt-4 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
            ${statTile('Input tokens', usage.input_tokens)}
            ${statTile('Output tokens', usage.output_tokens)}
            ${statTile('Stop reason', usage.stop_reason ?? '—')}
            ${statTile('Est. cost', usage.cost_label)}
        </dl>`;
}

function statTile(label, value) {
    return `<div class="rounded-lg bg-ink-100 px-3 py-2">
        <dt class="text-[10px] font-semibold uppercase tracking-wide text-ink-400">${label}</dt>
        <dd class="mt-0.5 font-mono text-[13px] text-ink-800">${escapeHtml(value)}</dd>
    </div>`;
}

function wirePlayground() {
    document.querySelectorAll('form[data-endpoint]').forEach((form) => {
        const output = document.querySelector(form.dataset.output);
        const button = form.querySelector('[type="submit"]');
        const renderer = renderers[form.dataset.render];

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const originalLabel = button.textContent;
            button.disabled = true;
            button.textContent = 'Running…';
            output.innerHTML =
                '<div class="animate-pulse rounded-lg border border-ink-200 bg-white p-4 text-sm text-ink-400">Calling the Anthropic API…</div>';

            try {
                const response = await fetch(form.dataset.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(Object.fromEntries(new FormData(form))),
                });

                const data = await response.json();

                if (!response.ok) {
                    const detail = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : data.error || 'Something went wrong.';
                    output.innerHTML = `<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><strong>Request failed.</strong><br>${escapeHtml(detail)}${data.hint ? `<br><span class="text-rose-600">${escapeHtml(data.hint)}</span>` : ''}</div>`;
                } else {
                    output.innerHTML = renderer(data);
                    decorateCodeBlocks(output);
                }
            } catch (error) {
                output.innerHTML = `<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">${escapeHtml(error.message)}</div>`;
            } finally {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });

    // Clicking a sample question fills the input next to it.
    document.querySelectorAll('[data-fill]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const target = document.querySelector(chip.dataset.fill);
            target.value = chip.dataset.value ?? chip.textContent.trim();
            target.focus();
        });
    });
}

/* -------------------------------------------------------------------------
 | Examples: reveal the recorded output
 |
 | Deliberately not a real execution — the output is stored with the example
 | so the page works with no API key, offline, forever. The short delay is
 | only so the reveal reads as a result rather than a layout shift.
 * ---------------------------------------------------------------------- */

function wireExampleRunner() {
    const button = document.querySelector('[data-run-example]');
    const output = document.querySelector('[data-example-output]');

    if (!button || !output) return;

    button.addEventListener('click', () => {
        button.disabled = true;
        button.textContent = 'Running…';

        setTimeout(() => {
            output.classList.remove('hidden');
            decorateCodeBlocks(output);
            button.textContent = 'Run again ▶';
            button.disabled = false;
            output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 320);
    });
}

/* -------------------------------------------------------------------------
 | Quiz: reveal the explanation as soon as an option is picked
 * ---------------------------------------------------------------------- */

function wireQuiz() {
    document.querySelectorAll('[data-question]').forEach((question) => {
        question.querySelectorAll('input[type="radio"]').forEach((input) => {
            input.addEventListener('change', () => {
                question.querySelector('[data-unanswered]')?.classList.add('hidden');
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    decorateCodeBlocks();
    trackReadingPosition();
    wirePlayground();
    wireExampleRunner();
    wireQuiz();
});
