<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brand['name'] ?? 'Laravel AI Hub' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
                        'fade-up': 'fadeUp .35s cubic-bezier(.16,1,.3,1)',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
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

{{-- Toast Notification --}}
<div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4"
     x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="pointer-events-auto flex items-center gap-2 max-w-lg rounded-xl border px-4 py-3 text-sm shadow-2xl backdrop-blur-xl"
         :class="toast.type==='error' ? 'border-rose-400/40 bg-rose-950/95 text-rose-100 shadow-rose-950/50' : 'border-accent/40 bg-ink-800/95 text-mist-100 shadow-accent/10'">
        <span x-show="toast.type!=='error'" class="flex h-2 w-2 rounded-full bg-accent animate-ping"></span>
        <span x-text="toast.message"></span>
    </div>
</div>

<header class="relative border-b border-white/[0.06] bg-ink-900/60 backdrop-blur-xl sticky top-0 z-40">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-emerald-500 text-ink-950 font-bold text-lg shadow-lg shadow-accent/20">
                AI
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold tracking-tight text-white sm:text-2xl">{{ $brand['name'] }}</h1>
                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-accent-soft">Studio v1.2</span>
                </div>
                <p class="text-xs text-slate-400">{{ $brand['tagline'] }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 rounded-xl border border-white/10 bg-ink-800/80 p-1">
                <template x-for="tab in tabs" :key="tab.id">
                    <button type="button" @click="activeTab=tab.id; tab.id==='analytics' && loadAnalytics()"
                            class="rounded-lg px-3.5 py-1.5 text-xs font-semibold transition"
                            :class="activeTab===tab.id ? 'bg-accent/20 text-accent-soft shadow-sm' : 'text-slate-400 hover:text-white'"
                            x-text="tab.label"></button>
                </template>
            </div>
        </div>
    </div>
</header>

<main class="relative mx-auto max-w-6xl px-4 py-6 sm:px-6 animate-fade-up">

    {{-- ==================== KEYS & MODELS ==================== --}}
    <section x-show="activeTab==='keys'" class="space-y-6">

        {{-- Top Summary Bar --}}
        <div class="panel rounded-2xl p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">Provider Credentials & Models</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                        <span>Configured:</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-accent-soft">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            <span x-text="savedProvidersCount() + ' of ' + providers.length + ' ready'"></span>
                        </span>
                        <span>·</span>
                        <span x-text="hasUnsavedChanges() ? '⚠️ Unsaved changes detected' : '✓ All settings synced'"></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-300 bg-white/5 border border-white/10 px-3 py-2 rounded-xl cursor-pointer hover:bg-white/10 transition">
                        <input type="checkbox" x-model="failoverEnabled" class="rounded border-slate-600 bg-ink-900 text-accent focus:ring-accent/30">
                        Automatic Failover
                    </label>

                    <button type="button" @click="saveSettings()" :disabled="busy || !hasUnsavedChanges()"
                            class="rounded-xl px-4 py-2 text-xs font-semibold transition flex items-center gap-2"
                            :class="hasUnsavedChanges() ? 'bg-accent text-ink-950 hover:bg-accent-soft shadow-lg shadow-accent/20 animate-pulse' : 'bg-white/10 text-slate-400 opacity-60 cursor-not-allowed'">
                        <span x-show="!busy">Save All Changes</span>
                        <span x-show="busy">Saving…</span>
                    </button>
                </div>
            </div>

            {{-- Default Provider Selection Pills --}}
            <div class="mt-6 border-t border-white/10 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Active Default Provider (Tried #1)</p>
                    <span class="text-[11px] text-slate-500">Click any provider to set as primary default</span>
                </div>

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach($boot['providers'] as $p)
                        <button type="button" @click="setDefaultProvider('{{ $p }}')"
                                class="group relative rounded-xl border p-3 text-left transition flex flex-col justify-between"
                                :class="defaultProvider==='{{ $p }}' ? 'border-accent/60 bg-accent/15 ring-1 ring-accent/40' : (form['{{ $p }}']?.has_key ? 'border-white/10 bg-ink-900/70 hover:border-white/20' : 'border-white/5 bg-ink-950/40 opacity-70 hover:opacity-100 hover:border-white/10')">
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-white">{{ $boot['labels'][$p] ?? ucfirst($p) }}</span>
                                
                                {{-- Status Dot in Pill --}}
                                <div class="flex items-center gap-1">
                                    <template x-if="isDirty('{{ $p }}')">
                                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-ping" title="Unsaved changes"></span>
                                    </template>
                                    <template x-if="!isDirty('{{ $p }}') && form['{{ $p }}']?.has_key">
                                        <span class="h-2 w-2 rounded-full bg-emerald-400" title="Saved & Active"></span>
                                    </template>
                                    <template x-if="!isDirty('{{ $p }}') && !form['{{ $p }}']?.has_key">
                                        <span class="h-2 w-2 rounded-full bg-slate-600" title="No API key saved"></span>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[10px]">
                                <span class="truncate font-mono text-slate-400" x-text="resolvedModel('{{ $p }}')"></span>
                                <template x-if="defaultProvider==='{{ $p }}'">
                                    <span class="shrink-0 font-bold uppercase tracking-wider text-accent-soft">★ Default</span>
                                </template>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Provider Cards Grid --}}
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($boot['providers'] as $p)
                <div class="panel rounded-2xl p-5 transition flex flex-col justify-between"
                     :class="{
                         'ring-1 ring-accent/50 border-accent/40': defaultProvider==='{{ $p }}',
                         'ring-1 ring-amber-500/50 border-amber-500/40 shadow-lg shadow-amber-500/5': isDirty('{{ $p }}')
                     }">
                    
                    {{-- Card Header --}}
                    <div>
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold shadow-inner"
                                      :class="form['{{ $p }}']?.has_key ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-white/5 text-slate-400 border border-white/10'">
                                    {{ strtoupper(substr($boot['labels'][$p] ?? $p, 0, 2)) }}
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-white text-base">{{ $boot['labels'][$p] ?? ucfirst($p) }}</h3>
                                        <template x-if="defaultProvider==='{{ $p }}'">
                                            <span class="rounded bg-accent/20 border border-accent/40 px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider text-accent-soft">Default</span>
                                        </template>
                                    </div>

                                    {{-- Status Badge --}}
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <template x-if="isDirty('{{ $p }}')">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                Unsaved edits
                                            </span>
                                        </template>
                                        <template x-if="!isDirty('{{ $p }}') && form['{{ $p }}']?.has_key">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                Saved & ready
                                            </span>
                                        </template>
                                        <template x-if="!isDirty('{{ $p }}') && !form['{{ $p }}']?.has_key">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400 border border-white/10">
                                                No key added
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer" title="Enable/Disable this provider">
                                    <input type="checkbox" x-model="form['{{ $p }}'].enabled" class="rounded border-slate-600 bg-ink-900 text-accent">
                                    <span class="text-[11px]" x-text="form['{{ $p }}'].enabled ? 'On' : 'Off'"></span>
                                </label>
                                <button type="button" @click="testProvider('{{ $p }}')" :disabled="busy"
                                        class="rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:bg-white/10 hover:text-white disabled:opacity-40 transition">
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- API Key Input --}}
                        <div class="mb-3.5">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">API Key</span>
                                <template x-if="form['{{ $p }}']?.has_key">
                                    <span class="text-[10px] text-emerald-400 font-mono">✓ Saved securely</span>
                                </template>
                            </div>
                            <input type="password" x-model="form['{{ $p }}'].api_key"
                                   @input="checkDirty('{{ $p }}')"
                                   :placeholder="form['{{ $p }}']?.has_key ? '•••••••• saved (type new to replace)' : 'Enter {{ $boot['labels'][$p] ?? ucfirst($p) }} API key'"
                                   class="w-full rounded-xl border bg-ink-900 px-3 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-accent/60 focus:outline-none focus:ring-2 focus:ring-accent/20 transition"
                                   :class="form['{{ $p }}'].api_key ? 'border-accent/50 bg-accent/5' : 'border-white/10'">
                        </div>

                        {{-- Model Selection --}}
                        <div class="mb-4">
                            <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Active Model</span>
                            <div class="flex flex-col gap-2">
                                <select x-model="form['{{ $p }}'].modelSelect"
                                        @change="checkDirty('{{ $p }}')"
                                        class="w-full rounded-xl border border-white/10 bg-ink-900 px-3 py-2.5 text-sm text-white focus:border-accent/60 focus:outline-none focus:ring-2 focus:ring-accent/20 transition">
                                    @foreach(($boot['popular'][$p] ?? []) as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                    <option value="__custom">Custom Model ID…</option>
                                </select>
                                <input type="text" x-show="form['{{ $p }}'].modelSelect==='__custom'"
                                       x-model="form['{{ $p }}'].customModel"
                                       @input="checkDirty('{{ $p }}')"
                                       placeholder="e.g. {{ $boot['popular'][$p][0] ?? 'custom-model-id' }}"
                                       class="w-full rounded-xl border border-white/10 bg-ink-900 px-3 py-2.5 font-mono text-sm text-white focus:border-accent/60 focus:outline-none focus:ring-2 focus:ring-accent/20">
                            </div>
                        </div>
                    </div>

                    {{-- Dedicated Per-Provider Action / Save Bar --}}
                    <div class="border-t border-white/10 pt-3 flex items-center justify-between gap-2">
                        <div class="text-[11px]">
                            <template x-if="isDirty('{{ $p }}')">
                                <span class="text-amber-300 font-medium">Modified · click save</span>
                            </template>
                            <template x-if="!isDirty('{{ $p }}') && defaultProvider!=='{{ $p }}'">
                                <button type="button" @click="setDefaultProvider('{{ $p }}')"
                                        class="text-slate-400 hover:text-accent-soft transition text-[11px] underline">
                                    Set as default
                                </button>
                            </template>
                            <template x-if="!isDirty('{{ $p }}') && defaultProvider==='{{ $p }}'">
                                <span class="text-accent-soft font-semibold text-[11px]">Current default</span>
                            </template>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <template x-if="isDirty('{{ $p }}')">
                                <button type="button" @click="revertProvider('{{ $p }}')"
                                        class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs text-slate-400 hover:text-white hover:bg-white/5 transition">
                                    Revert
                                </button>
                            </template>

                            {{-- Direct Save Button Beside User on the Card --}}
                            <button type="button" @click="saveSingleProvider('{{ $p }}')" :disabled="busy || !isDirty('{{ $p }}')"
                                    class="rounded-lg px-3.5 py-1.5 text-xs font-semibold transition flex items-center gap-1.5"
                                    :class="isDirty('{{ $p }}') ? 'bg-accent text-ink-950 hover:bg-accent-soft shadow-md shadow-accent/20 font-bold' : 'bg-white/5 text-slate-500 opacity-50 cursor-not-allowed'">
                                <span x-show="!busy">Save</span>
                                <span x-show="busy">…</span>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        {{-- Footer Bulk Save --}}
        <div class="panel rounded-2xl p-5 flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-slate-400">
                <span class="font-semibold text-white">Save all providers in one click:</span>
                Stores API keys, model selections, and default priorities to application storage.
            </div>
            <button type="button" @click="saveSettings()" :disabled="busy"
                    class="rounded-xl bg-accent px-6 py-3 text-sm font-semibold text-ink-950 hover:bg-accent-soft disabled:opacity-40 shadow-lg shadow-accent/15 transition">
                <span x-show="!busy">Save All Keys & Models</span>
                <span x-show="busy">Saving…</span>
            </button>
        </div>
    </section>

    {{-- ==================== PRIORITY / FAILOVER ==================== --}}
    <section x-show="activeTab==='priority'" class="space-y-6">
        <div class="panel rounded-2xl p-6">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">Failover Priority Chain</h2>
                    <p class="mt-1 text-sm text-slate-400">#1 is attempted first. If rate-limited or unavailable, AI Hub fails over down the chain.</p>
                </div>
                <label class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-sm text-slate-300 bg-white/5">
                    <input type="checkbox" x-model="failoverEnabled" class="rounded border-slate-600 bg-ink-900 text-accent">
                    Failover enabled
                </label>
            </div>

            <div class="mt-6 space-y-3">
                <template x-for="(p, index) in priority" :key="'pri-'+p">
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-ink-950/60 p-4 transition hover:border-accent/30">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-mono text-sm font-bold shadow-inner"
                             :class="index===0 ? 'bg-accent text-ink-950 shadow-accent/20' : 'bg-white/5 text-slate-400 border border-white/10'"
                             x-text="'#'+(index+1)"></div>
                        
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-white" x-text="labels[p]"></p>
                                <template x-if="form[p]?.has_key">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.2 text-[9px] font-medium text-emerald-300 border border-emerald-500/30">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Saved
                                    </span>
                                </template>
                                <template x-if="!form[p]?.has_key">
                                    <span class="rounded bg-slate-800 px-2 py-0.2 text-[9px] text-slate-400 border border-white/5">No key</span>
                                </template>
                            </div>
                            <p class="truncate font-mono text-[11px] text-slate-500 mt-0.5">
                                <span x-text="resolvedModel(p)"></span>
                                · <span x-text="form[p]?.enabled ? 'enabled' : 'disabled'"></span>
                            </p>
                        </div>
                        
                        <div class="flex shrink-0 gap-1.5">
                            <button type="button" @click="movePriority(index,-1)" :disabled="index===0"
                                    class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/10 disabled:opacity-30">↑</button>
                            <button type="button" @click="movePriority(index,1)" :disabled="index===priority.length-1"
                                    class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/10 disabled:opacity-30">↓</button>
                            <button type="button" @click="makeFirst(index)"
                                    class="rounded-lg border border-accent/30 px-3 py-1.5 text-xs font-semibold text-accent-soft hover:bg-accent/15">Make #1</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-6 rounded-xl border border-white/10 bg-ink-900/70 px-4 py-3.5 font-mono text-xs text-slate-400 flex items-center gap-2 overflow-x-auto">
                <span class="text-slate-500 font-bold shrink-0">Chain:</span>
                <span class="text-accent-soft whitespace-nowrap" x-text="priority.map((p,i)=> (i+1)+'. '+labels[p]).join('  →  ')"></span>
            </div>

            <button type="button" @click="savePriority()" :disabled="busy"
                    class="mt-6 rounded-xl bg-accent px-6 py-3 text-sm font-semibold text-ink-950 hover:bg-accent-soft disabled:opacity-40 shadow-lg shadow-accent/15">
                Save Priority Chain
            </button>
        </div>
    </section>

    {{-- ==================== ANALYTICS ==================== --}}
    <section x-show="activeTab==='analytics'" class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-white">Usage Analytics</h2>
                <p class="mt-1 text-sm text-slate-400">Past 30 days telemetry: cost, latency, failure rates & token volumes</p>
            </div>
            <button type="button" @click="loadAnalytics()" class="rounded-xl border border-white/10 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-white/10 transition">Refresh</button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Spend (30d)</p>
                <p class="mt-2 text-2xl font-bold text-white" x-text="'$'+(analytics.summary?.total_cost_usd ?? 0).toFixed(4)"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.requests ?? 0)+' total requests'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Failure Rate</p>
                <p class="mt-2 text-2xl font-bold" :class="(analytics.summary?.failure_rate||0)>5?'text-rose-400':'text-emerald-400'"
                   x-text="(analytics.summary?.failure_rate ?? 0)+'%'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.failures ?? 0)+' failed attempts'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">JSON Recovery</p>
                <p class="mt-2 text-2xl font-bold text-amber-300" x-text="(analytics.summary?.json_recovery_rate ?? 0)+'%'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.json_recovered ?? 0)+' auto-repaired'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Latency (p95)</p>
                <p class="mt-2 text-2xl font-bold text-white" x-text="(analytics.latency?.p95 ?? 0)+' ms'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="'p50: '+(analytics.latency?.p50 ?? 0)+'ms · p99: '+(analytics.latency?.p99 ?? 0)+'ms'"></p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="panel rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white">Spend by Provider</h3>
                <ul class="mt-4 space-y-3.5">
                    <template x-if="!(analytics.cost_by_provider||[]).length">
                        <li class="text-sm text-slate-500">No requests recorded yet.</li>
                    </template>
                    <template x-for="row in (analytics.cost_by_provider||[])" :key="row.provider">
                        <li>
                            <div class="mb-1.5 flex justify-between text-sm">
                                <span class="font-medium text-slate-200" x-text="labels[row.provider]||row.provider"></span>
                                <span class="font-mono text-accent-soft font-semibold" x-text="'$'+Number(row.cost||0).toFixed(4)"></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-gradient-to-r from-accent to-emerald-400" :style="'width:'+costBar(row.cost)+'%'"></div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="panel rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white">Top Tracked Jobs</h3>
                <ul class="mt-4 max-h-64 space-y-2 overflow-y-auto scroll-thin">
                    <template x-if="!(analytics.top_jobs||[]).length">
                        <li class="text-sm text-slate-500">No job traces logged. Use <code class="text-accent-soft bg-white/5 px-1 rounded">->forJob('invoice')</code>.</li>
                    </template>
                    <template x-for="job in (analytics.top_jobs||[])" :key="job.job">
                        <li class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-2.5">
                            <span class="truncate font-mono text-xs text-slate-300" x-text="job.job"></span>
                            <span class="shrink-0 font-mono text-[11px] text-slate-400" x-text="job.tokens+' tokens'"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="panel rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white">Daily Cost Trend</h3>
            <div class="mt-4 flex h-36 items-end gap-1.5">
                <template x-if="!(analytics.daily||[]).length">
                    <p class="text-sm text-slate-500">No daily logs yet.</p>
                </template>
                <template x-for="day in (analytics.daily||[])" :key="day.day">
                    <div class="group relative flex flex-1 flex-col items-center justify-end">
                        <div class="w-full rounded-t bg-accent/70 transition group-hover:bg-accent shadow-sm"
                             :style="'height:'+dailyBar(day.cost)+'%'"></div>
                        <span class="mt-1 hidden text-[9px] text-slate-500 sm:block" x-text="String(day.day).slice(5)"></span>
                    </div>
                </template>
            </div>
        </div>
    </section>

</main>

{{-- Sticky Floating Save Banner (appears anywhere if user has unsaved edits) --}}
<div x-show="hasUnsavedChanges() && activeTab==='keys'"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-8"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-8"
     class="fixed inset-x-0 bottom-6 z-40 flex justify-center px-4">
    <div class="flex items-center gap-4 rounded-2xl border border-accent/40 bg-ink-900/95 px-5 py-3.5 shadow-2xl shadow-accent/15 backdrop-blur-2xl">
        <div class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-ping"></span>
            <span class="text-sm font-semibold text-white">
                Unsaved changes in:
                <span class="text-accent-soft" x-text="dirtyProviders().map(p => labels[p] || p).join(', ')"></span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="revertAll()"
                    class="rounded-xl border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/10 transition">
                Discard
            </button>
            <button type="button" @click="saveSettings()" :disabled="busy"
                    class="rounded-xl bg-accent px-4 py-1.5 text-xs font-bold text-ink-950 hover:bg-accent-soft shadow-md shadow-accent/20 transition">
                Save All
            </button>
        </div>
    </div>
</div>

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
    const savedState = {};

    providers.forEach(p => {
        const row = settings.providers?.[p] || {};
        const model = row.model || popular[p]?.[0] || '';
        const isPopular = (popular[p] || []).includes(model);
        const hasKey = !!row.has_key;
        const enabled = row.enabled !== false;

        form[p] = {
            api_key: '',
            has_key: hasKey,
            enabled: enabled,
            modelSelect: isPopular ? model : '__custom',
            customModel: isPopular ? '' : model,
            _dirty: false,
        };

        savedState[p] = {
            model: isPopular ? model : model,
            has_key: hasKey,
            enabled: enabled,
        };
    });

    return {
        providers,
        popular,
        labels,
        form,
        savedState,
        defaultProvider: settings.default || 'gemini',
        priority: [...(boot.priority || providers)],
        failoverEnabled: boot.failover_enabled !== false,
        activeTab: 'keys',
        tabs: [
            { id: 'keys', label: 'Keys & Models' },
            { id: 'priority', label: 'Priority Chain' },
            { id: 'analytics', label: 'Usage Analytics' },
        ],
        busy: false,
        toast: { show: false, message: '', type: 'ok' },
        analytics: { summary: {}, cost_by_provider: [], latency: {}, top_jobs: [], daily: [] },
        routes: {
            settings: @json(route('ai-hub.api.settings')),
            provider: @json(route('ai-hub.api.provider')),
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

        checkDirty(p) {
            const f = this.form[p];
            const s = this.savedState[p];
            if (!f || !s) return;
            const keyEntered = (f.api_key || '').trim().length > 0;
            const modelChanged = this.resolvedModel(p) !== s.model;
            const enabledChanged = f.enabled !== s.enabled;
            f._dirty = keyEntered || modelChanged || enabledChanged;
        },

        isDirty(p) {
            const f = this.form[p];
            const s = this.savedState[p];
            if (!f || !s) return false;
            if ((f.api_key || '').trim().length > 0) return true;
            if (this.resolvedModel(p) !== s.model) return true;
            if (f.enabled !== s.enabled) return true;
            return false;
        },

        dirtyProviders() {
            return this.providers.filter(p => this.isDirty(p));
        },

        hasUnsavedChanges() {
            return this.dirtyProviders().length > 0;
        },

        savedProvidersCount() {
            return this.providers.filter(p => this.form[p]?.has_key).length;
        },

        setDefaultProvider(p) {
            this.defaultProvider = p;
            this.checkDirty(p);
        },

        revertProvider(p) {
            const s = this.savedState[p];
            if (!s) return;
            const isPopular = (this.popular[p] || []).includes(s.model);
            this.form[p].api_key = '';
            this.form[p].modelSelect = isPopular ? s.model : '__custom';
            this.form[p].customModel = isPopular ? '' : s.model;
            this.form[p].enabled = s.enabled;
            this.form[p]._dirty = false;
        },

        revertAll() {
            this.providers.forEach(p => this.revertProvider(p));
        },

        async saveSingleProvider(p) {
            this.busy = true;
            try {
                const model = this.resolvedModel(p);
                const data = await this.request(this.routes.provider, {
                    provider: p,
                    api_key: this.form[p].api_key || null,
                    model: model === '—' ? null : model,
                    enabled: !!this.form[p].enabled,
                    make_default: this.defaultProvider === p,
                });
                
                this.applyBoot(data.data);
                this.notify((this.labels[p] || p) + ' saved successfully!');
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
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
                this.notify(data.message || 'All keys & models saved.');
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
                this.notify(data.message || 'Priority chain saved.');
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
                this.notify(`✓ ${data.provider} OK · ${data.model} · ${data.latency_ms}ms`);
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
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
                
                const hasKey = !!row.has_key;
                const enabled = row.enabled !== false;
                const model = row.model || this.popular[p]?.[0] || '';
                const isPopular = (this.popular[p] || []).includes(model);
                
                this.form[p].has_key = hasKey;
                this.form[p].api_key = '';
                this.form[p].enabled = enabled;
                this.form[p].modelSelect = isPopular ? model : '__custom';
                this.form[p].customModel = isPopular ? '' : model;
                this.form[p]._dirty = false;

                this.savedState[p] = {
                    model: model,
                    has_key: hasKey,
                    enabled: enabled,
                };
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
