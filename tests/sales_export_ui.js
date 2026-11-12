// Run: node tests/sales_export_ui.js
// Exercise the actual browser controller with deterministic AJAX and timers.
const assert = require('assert').strict;
const fs = require('fs');
const vm = require('vm');
const nodes = new Map();
const requests = [];
const timers = new Map();
let sequence = 0;

function node(selector) {
    if (!nodes.has(selector)) {
        nodes.set(selector, {
            visible: true, properties: {}, attributes: {}, handlers: {}, children: [], value: '',
            toggle(value) { this.visible = value; return this; },
            prop(key, value) { this.properties[key] = value; return this; },
            attr(key, value) { this.attributes[key] = value; return this; },
            text(value) { this.value = value; return this; },
            empty() { this.children = []; return this; },
            show() { this.visible = true; return this; },
            hide() { this.visible = false; return this; },
            append(...values) { this.children.push(...values); return this; },
            appendTo(target) { (typeof target === 'string' ? node(target) : target).children.push(this); return this; },
            addClass() { return this; },
            on(event, callback) { this.handlers[event] = callback; return this; }
        });
    }
    return nodes.get(selector);
}
function $(selector) {
    if (typeof selector === 'function') { selector(); return; }
    return node(selector.startsWith('<') ? selector + (++sequence) : selector);
}
$.each = (object, callback) => Object.entries(object).forEach(([key, value]) => callback(key, value));
$.ajax = options => {
    const request = {
        options,
        done(callback) { this.success = callback; return this; },
        fail(callback) { this.failure = callback; return this; },
        always(callback) { this.finish = callback; return this; },
        resolve(data) { this.success(data); this.finish(); },
        reject(status) { this.failure({ status }); this.finish(); }
    };
    requests.push(request);
    return request;
};
const urls = Object.fromEntries(['start', 'status', 'step', 'delete', 'download'].map(action => [action, '/admin/' + action]));
const messages = Object.fromEntries(['text_ready', 'text_running', 'text_paused', 'text_complete', 'text_progress', 'error_network', 'text_confirm_delete', 'button_download'].map(key => [key, key]));
vm.runInNewContext(fs.readFileSync(__dirname + '/../upload/admin/view/javascript/sales_export.js', 'utf8'), {
    jQuery: $, document: { getElementById: () => ({ textContent: JSON.stringify({ urls, messages }) }) },
    window: { confirm: () => true },
    setTimeout(callback) { const id = ++sequence; timers.set(id, callback); return id; },
    clearTimeout(id) { timers.delete(id); }
});
function click(id) { node(id).handlers.click(); }
function tick() {
    const first = timers.entries().next().value;
    assert.ok(first, 'Expected scheduled request');
    timers.delete(first[0]); first[1]();
}
function last() { return requests[requests.length - 1]; }
const job = { id: 'example-id', rows: 100, bytes: 1024, table: 'order', tables_done: 0, tables_total: 8, created: 'UTC', complete: false,
    counts: { order: 100, order_product: 0, order_option: 0, order_total: 0, order_history: 0, customer: 0, address: 0, product: 0 } };

assert.equal(last().options.type, 'GET');
assert.equal(node('#export-start').properties.disabled, true);
last().resolve({ job: null });
assert.equal(node('#export-start').properties.disabled, false);
click('#export-start');
assert.equal(last().options.type, 'POST');
last().resolve({ job });
tick();
assert.equal(last().options.url, urls.step);
assert.equal(last().options.data.job, job.id);
click('#export-pause');
last().resolve({ job });
assert.equal(timers.size, 0, 'Pause must not schedule more work');
assert.equal(node('#export-resume').visible, true);
click('#export-resume'); tick();
last().reject(504);
assert.equal(timers.size, 1, 'Transient timeout should retry');
click('#export-pause');
assert.equal(timers.size, 0, 'Pause must cancel a pending retry');
click('#export-resume'); tick();
last().resolve({ job: { ...job, complete: true, tables_done: 8 } });
assert.equal(timers.size, 0, 'Completed export must stop polling');
assert.equal(node('#export-delete').properties.disabled, false);
const rows = node('#export-counts tbody').children;
assert.equal(rows.length, 8);
rows.forEach(row => {
    const link = row.children[2].children[0];
    assert.ok(link.attributes.href.includes('job=example-id&table='));
});
click('#export-delete');
assert.equal(last().options.url, urls.delete);
last().resolve({ job: null });
assert.equal(node('#export-start').visible, true);
click('#export-start');
last().resolve({ error: 'busy', retry: true });
assert.equal(node('#export-start').properties.disabled, true, 'Retry must prevent duplicate start clicks');
tick(); last().resolve({ job }); tick();
last().reject(403);
assert.equal(node('#export-error').visible, true);
assert.equal(timers.size, 0, 'Authentication failure must stop work');
console.log('PASS: start, pause, resume, timeout retry, pause during retry, all eight CSV download links, delete and expired login.');
