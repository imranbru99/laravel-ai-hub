<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brand['name'] ?? 'Laravel AI Hub' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink: { 950: '#070b12', 900: '#0c121c', 800: '#121a27', 700: '#1a2435' },
                        mist: { 100: '#e8eef7', 300: '#b6c2d6', 400: '#7b8aa3', 500: '#5c6b84' },
                        accent: { DEFAULT: '#2dd4bf', soft: '#5eead4', dim: '#14b8a6' },
                    },
                    animation: {
                        'fade-up': 'fadeUp .45s cubic-bezier(.16,1,.3,1)',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: 0, transform: 'translateY(10px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak]{display:none!important}
        body{
            background:
                radial-gradient(ellipse 80% 50% at 10% -10%, rgba(45,212,191,.16), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 0%, rgba(56,189,248,.1), transparent 50%),
                #070b12;
        }
        .grid-bg{
            background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
            background-size:42px 42px;
            mask-image:radial-gradient(ellipse 70% 55% at 50% 20%,#000,transparent);
        }
        .panel{background:rgba(18,26,39,.82);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(16px)}
        .scroll-thin::-webkit-scrollbar{width:6px;height:6px}
        .scroll-thin::-webkit-scrollbar-thumb{background:rgba(125,140,160,.35);border-radius:999px}
    </style>
</head>
<body class="min-h-full font-sans text-slate-100 antialiased"
      x-data="aiHubStudio(@js($boot))"
      x-init="init()"
      x-cloak>

<div class="pointer-events-none fixed inset-0 grid-bg" aria-hidden="true"></div>

{{-- Toast --}}
<div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4"
     x-show="toast.show" x-transition>
    <div class="pointer-events-auto max-w-lg rounded-xl border px-4 py-3 text-sm shadow-xl"
         :class="toast.type==='error' ? 'border-rose-400/30 bg-rose-950/90 text-rose-100' : 'border-accent/30 bg-ink-800/95 text-mist-100'"
         x-text="toast.message"></div>
</div>

<header class="relative border-b border-white/[0.06] bg-ink-900/50 backdrop-blur-xl">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6">
        <div>
            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-accent-soft">Imran Dev BD</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $brand['name'] }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $brand['tagline'] }}</p>
        </div>
        <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-ink-800/60 p-1">
            <template x-for="tab in tabs" :key="tab.id">
                <button type="button" @click="activeTab=tab.id; tab.id==='analytics' && loadAnalytics()"
                        class="rounded-lg px-3.5 py-2 text-sm font-medium transition"
                        :class="activeTab===tab.id ? 'bg-accent/20 text-accent-soft' : 'text-slate-400 hover:text-white'"
                        x-text="tab.label"></button>
            </template>
        </div>
    </div>
</header>

<main class="relative mx-auto max-w-6xl px-4 py-8 sm:px-6 animate-fade-up">

    {{-- KEYS & MODELS --}}
    <section x-show="activeTab==='keys'" class="space-y-6">
        <div class="panel rounded-2xl p-6">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">API keys & models</h2>
                    <p class="mt-1 text-sm text-slate-400">Paste keys, pick models — saved for the whole app.</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" x-model="failoverEnabled" class="rounded border-slate-600 bg-ink-900 text-accent focus:ring-accent/30">
                    Auto failover on
                </label>
            </div>

            <div class="mb-6">
                <p class="mb-2 text-[11px] font-medium uppercase tracking-wider text-slate-500">Default provider</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach($boot['providers'] as $p)
                        <button type="button" @click="defaultProvider='{{ $p }}'"
                                class="rounded-xl border px-3 py-3 text-left transition"
                                :class="defaultProvider==='{{ $p }}' ? 'border-accent/50 bg-accent/10' : 'border-white/10 hover:border-white/20'">
                            <p class="text-sm font-semibold text-white">{{ $boot['labels'][$p] ?? ucfirst($p) }}</p>
                            <p class="mt-0.5 truncate font-mono text-[10px] text-slate-500" x-text="form['{{ $p }}']?.model || '—'"></p>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($boot['providers'] as $p)
                    <div class="rounded-2xl border border-white/10 bg-ink-950/40 p-5 transition"
                         :class="defaultProvider==='{{ $p }}' && 'ring-1 ring-accent/40'">
                        <div class="mb-4 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent/15 text-xs font-bold text-accent-soft">
                                    {{ strtoupper(substr($boot['labels'][$p] ?? $p, 0, 2)) }}
                                </span>
                                <div>
                                    <h3 class="font-semibold text-white">{{ $boot['labels'][$p] ?? ucfirst($p) }}</h3>
                                    <p class="text-[11px] text-slate-500" x-text="form['{{ $p }}']?.has_key ? 'Key saved' : 'No key yet'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-1.5 text-xs text-slate-400">
                                    <input type="checkbox" x-model="form['{{ $p }}'].enabled" class="rounded border-slate-600 bg-ink-900 text-accent">
                                    On
                                </label>
                                <button type="button" @click="testProvider('{{ $p }}')" :disabled="busy"
                                        class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs text-slate-300 hover:bg-white/5 disabled:opacity-40">Test</button>
                            </div>
                        </div>

                        <label class="mb-3 block">
                            <span class="mb-1.5 block text-[11px] uppercase tracking-wider text-slate-500">API key</span>
                            <input type="password" x-model="form['{{ $p }}'].api_key"
                                   :placeholder="form['{{ $p }}']?.has_key ? '•••• saved — paste to replace' : 'Paste API key'"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-accent/40 focus:outline-none focus:ring-2 focus:ring-accent/15">
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[11px] uppercase tracking-wider text-slate-500">Model</span>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <select x-model="form['{{ $p }}'].modelSelect"
                                        class="w-full rounded-xl border border-white/10 bg-ink-900 px-3 py-2.5 text-sm text-white focus:border-accent/40 focus:outline-none">
                                    @foreach(($boot['popular'][$p] ?? []) as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                    <option value="__custom">Custom…</option>
                                </select>
                                <input type="text" x-show="form['{{ $p }}'].modelSelect==='__custom'" x-model="form['{{ $p }}'].customModel"
                                       placeholder="custom-model-id"
                                       class="w-full rounded-xl border border-white/10 bg-ink-900 px-3 py-2.5 font-mono text-sm text-white focus:border-accent/40 focus:outline-none">
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" @click="saveSettings()" :disabled="busy"
                        class="rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-ink-950 hover:bg-accent-soft disabled:opacity-40">
                    <span x-show="!busy">Save keys & models</span>
                    <span x-show="busy">Saving…</span>
                </button>
            </div>
        </div>
    </section>

    {{-- PRIORITY --}}
    <section x-show="activeTab==='priority'" class="space-y-6">
        <div class="panel rounded-2xl p-6">
            <div class="mb-2 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">Provider priority</h2>
                    <p class="mt-1 text-sm text-slate-400">#1 is tried first. If it fails, AI Hub moves to #2 automatically.</p>
                </div>
                <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-slate-300">
                    <input type="checkbox" x-model="failoverEnabled" class="rounded border-slate-600 bg-ink-900 text-accent">
                    Failover enabled
                </label>
            </div>

            <div class="mt-6 space-y-3">
                <template x-for="(p, index) in priority" :key="'pri-'+p">
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-ink-950/50 p-4 transition hover:border-accent/25">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-mono text-sm font-bold"
                             :class="index===0 ? 'bg-accent text-ink-950' : 'bg-white/5 text-slate-400'"
                             x-text="'#'+(index+1)"></div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-white" x-text="labels[p]"></p>
                            <p class="truncate font-mono text-[11px] text-slate-500">
                                <span x-text="resolvedModel(p)"></span>
                                · <span x-text="form[p]?.has_key ? 'key ready' : 'no key'"></span>
                                · <span x-text="form[p]?.enabled ? 'enabled' : 'disabled'"></span>
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <button type="button" @click="movePriority(index,-1)" :disabled="index===0"
                                    class="rounded-lg border border-white/10 px-2.5 py-2 text-xs text-slate-300 hover:bg-white/5 disabled:opacity-30">↑</button>
                            <button type="button" @click="movePriority(index,1)" :disabled="index===priority.length-1"
                                    class="rounded-lg border border-white/10 px-2.5 py-2 text-xs text-slate-300 hover:bg-white/5 disabled:opacity-30">↓</button>
                            <button type="button" @click="makeFirst(index)"
                                    class="rounded-lg border border-accent/30 px-2.5 py-2 text-xs text-accent-soft hover:bg-accent/10">First</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-6 rounded-xl border border-white/10 bg-ink-900/50 px-4 py-3 font-mono text-[11px] text-slate-400">
                Chain:
                <span class="text-accent-soft" x-text="priority.map((p,i)=> (i+1)+'. '+labels[p]).join('  →  ')"></span>
            </div>

            <button type="button" @click="savePriority()" :disabled="busy"
                    class="mt-6 rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-ink-950 hover:bg-accent-soft disabled:opacity-40">
                Save priority
            </button>
        </div>
    </section>

    {{-- ANALYTICS --}}
    <section x-show="activeTab==='analytics'" class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-white">Analytics</h2>
                <p class="mt-1 text-sm text-slate-400">Last 30 days · cost, failures, JSON recovery, latency</p>
            </div>
            <button type="button" @click="loadAnalytics()" class="rounded-lg border border-white/10 px-3 py-2 text-xs text-slate-300 hover:bg-white/5">Refresh</button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] uppercase tracking-wider text-slate-500">Cost (30d)</p>
                <p class="mt-2 text-2xl font-semibold text-white" x-text="'$'+(analytics.summary?.total_cost_usd ?? 0).toFixed(4)"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.requests ?? 0)+' requests'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] uppercase tracking-wider text-slate-500">Failure rate</p>
                <p class="mt-2 text-2xl font-semibold" :class="(analytics.summary?.failure_rate||0)>5?'text-rose-300':'text-accent-soft'"
                   x-text="(analytics.summary?.failure_rate ?? 0)+'%'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.failures ?? 0)+' failed'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] uppercase tracking-wider text-slate-500">JSON recovered</p>
                <p class="mt-2 text-2xl font-semibold text-amber-300" x-text="(analytics.summary?.json_recovery_rate ?? 0)+'%'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.json_recovered ?? 0)+' repaired'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] uppercase tracking-wider text-slate-500">Latency p95</p>
                <p class="mt-2 text-2xl font-semibold text-white" x-text="(analytics.latency?.p95 ?? 0)+' ms'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="'p50 '+(analytics.latency?.p50 ?? 0)+' · p99 '+(analytics.latency?.p99 ?? 0)"></p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="panel rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white">Cost by provider</h3>
                <ul class="mt-4 space-y-3">
                    <template x-if="!(analytics.cost_by_provider||[]).length">
                        <li class="text-sm text-slate-500">No usage logged yet.</li>
                    </template>
                    <template x-for="row in (analytics.cost_by_provider||[])" :key="row.provider">
                        <li>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="capitalize text-slate-200" x-text="labels[row.provider]||row.provider"></span>
                                <span class="font-mono text-accent-soft" x-text="'$'+Number(row.cost||0).toFixed(4)"></span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-accent/80" :style="'width:'+costBar(row.cost)+'%'"></div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="panel rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white">Top jobs by tokens</h3>
                <ul class="mt-4 max-h-64 space-y-2 overflow-y-auto scroll-thin">
                    <template x-if="!(analytics.top_jobs||[]).length">
                        <li class="text-sm text-slate-500">No job traces yet. Use <code class="text-accent-soft">->forJob()</code>.</li>
                    </template>
                    <template x-for="job in (analytics.top_jobs||[])" :key="job.job">
                        <li class="flex items-center justify-between rounded-xl border border-white/5 px-3 py-2.5">
                            <span class="truncate font-mono text-xs text-slate-300" x-text="job.job"></span>
                            <span class="shrink-0 font-mono text-[11px] text-slate-500" x-text="job.tokens+' tok'"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="panel rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white">Daily spend</h3>
            <div class="mt-4 flex h-36 items-end gap-1">
                <template x-if="!(analytics.daily||[]).length">
                    <p class="text-sm text-slate-500">No daily data yet.</p>
                </template>
                <template x-for="day in (analytics.daily||[])" :key="day.day">
                    <div class="group relative flex flex-1 flex-col items-center justify-end">
                        <div class="w-full rounded-t bg-accent/70 transition group-hover:bg-accent"
                             :style="'height:'+dailyBar(day.cost)+'%'"></div>
                        <span class="mt-1 hidden text-[9px] text-slate-500 sm:block" x-text="String(day.day).slice(5)"></span>
                    </div>
                </template>
            </div>
        </div>
    </section>
</main>

<footer class="relative border-t border-white/[0.05] py-8 text-center text-xs text-slate-500">
    Laravel AI Hub · Developed by
    <a href="https://imrandev.bd/" target="_blank" class="text-accent-soft hover:underline">Imran Dev BD</a>
    · <a href="https://imrandev.bd/contact" target="_blank" class="hover:underline">Contact</a>
</footer>

<script>
function aiHubStudio(boot) {
    const providers = boot.providers || ['openai','gemini','claude','grok','deepseek','mistral','groq','ollama','openrouter'];
    const popular = boot.popular || {};
    const settings = boot.settings || {};
    const labels = boot.labels || {};
    const form = {};

    providers.forEach(p => {
        const row = settings.providers?.[p] || {};
        const model = row.model || popular[p]?.[0] || '';
        const isPopular = (popular[p] || []).includes(model);
        form[p] = {
            api_key: '',
            has_key: !!row.has_key,
            enabled: row.enabled !== false,
            modelSelect: isPopular ? model : '__custom',
            customModel: isPopular ? '' : model,
            get model() {
                return this.modelSelect === '__custom' ? this.customModel : this.modelSelect;
            }
        };
    });

    return {
        providers,
        popular,
        labels,
        form,
        defaultProvider: settings.default || 'openai',
        priority: [...(boot.priority || providers)],
        failoverEnabled: boot.failover_enabled !== false,
        activeTab: 'keys',
        tabs: [
            { id: 'keys', label: 'Keys & Models' },
            { id: 'priority', label: 'Priority' },
            { id: 'analytics', label: 'Analytics' },
        ],
        busy: false,
        toast: { show: false, message: '', type: 'ok' },
        analytics: { summary: {}, cost_by_provider: [], latency: {}, top_jobs: [], daily: [] },
        routes: {
            settings: @json(route('ai-hub.api.settings')),
            priority: @json(route('ai-hub.api.priority')),
            test: @json(route('ai-hub.api.test')),
            analytics: @json(route('ai-hub.api.analytics')),
        },

        init() {},

        resolvedModel(p) {
            const f = this.form[p];
            if (!f) return '—';
            return f.modelSelect === '__custom' ? (f.customModel || '—') : f.modelSelect;
        },

        movePriority(index, dir) {
            const next = index + dir;
            if (next < 0 || next >= this.priority.length) return;
            const copy = [...this.priority];
            const tmp = copy[index];
            copy[index] = copy[next];
            copy[next] = tmp;
            this.priority = copy;
            this.defaultProvider = this.priority[0];
        },

        makeFirst(index) {
            if (index === 0) return;
            const item = this.priority.splice(index, 1)[0];
            this.priority.unshift(item);
            this.defaultProvider = this.priority[0];
        },

        async saveSettings() {
            this.busy = true;
            try {
                const providers = {};
                this.providers.forEach(p => {
                    providers[p] = {
                        api_key: this.form[p].api_key || null,
                        model: this.resolvedModel(p),
                        enabled: !!this.form[p].enabled,
                    };
                });
                const data = await this.request(this.routes.settings, {
                    default: this.defaultProvider,
                    failover_enabled: this.failoverEnabled,
                    providers,
                });
                this.applyBoot(data.data);
                this.notify(data.message || 'Saved');
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        async savePriority() {
            this.busy = true;
            try {
                const data = await this.request(this.routes.priority, {
                    priority: this.priority,
                    failover_enabled: this.failoverEnabled,
                    default: this.priority[0],
                });
                this.applyBoot(data.data);
                this.notify(data.message || 'Priority saved');
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        async testProvider(p) {
            this.busy = true;
            try {
                const data = await this.request(this.routes.test, {
                    provider: p,
                    model: this.resolvedModel(p),
                    api_key: this.form[p].api_key || null,
                });
                this.notify(`${data.provider} OK · ${data.model} · ${data.latency_ms}ms`);
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        async loadAnalytics() {
            try {
                const data = await this.request(this.routes.analytics, null, 'GET');
                this.analytics = {
                    summary: data.summary || {},
                    cost_by_provider: data.cost_by_provider || [],
                    latency: data.latency || {},
                    top_jobs: data.top_jobs || [],
                    daily: data.daily || [],
                };
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },

        costBar(cost) {
            const max = Math.max(...(this.analytics.cost_by_provider || []).map(r => Number(r.cost || 0)), 0.000001);
            return Math.max(4, Math.round((Number(cost || 0) / max) * 100));
        },

        dailyBar(cost) {
            const max = Math.max(...(this.analytics.daily || []).map(r => Number(r.cost || 0)), 0.000001);
            return Math.max(3, Math.round((Number(cost || 0) / max) * 100));
        },

        applyBoot(boot) {
            if (!boot) return;
            this.defaultProvider = boot.settings?.default || this.defaultProvider;
            this.priority = [...(boot.priority || this.priority)];
            this.failoverEnabled = boot.failover_enabled !== false;
            (boot.providers || this.providers).forEach(p => {
                const row = boot.settings?.providers?.[p] || {};
                if (!this.form[p]) return;
                this.form[p].has_key = !!row.has_key;
                this.form[p].api_key = '';
                this.form[p].enabled = row.enabled !== false;
                const model = row.model || this.popular[p]?.[0] || '';
                const isPopular = (this.popular[p] || []).includes(model);
                this.form[p].modelSelect = isPopular ? model : '__custom';
                this.form[p].customModel = isPopular ? '' : model;
            });
        },

        async request(url, body, method = 'POST') {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: method === 'GET' ? undefined : JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Request failed');
            return data;
        },

        notify(message, type = 'ok') {
            this.toast = { show: true, message, type };
            clearTimeout(this._t);
            this._t = setTimeout(() => this.toast.show = false, 3600);
        },
    };
}
</script>
</body>
</html>
