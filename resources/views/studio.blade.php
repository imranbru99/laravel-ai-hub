<!DOCTYPE html>
<html lang="en" class="h-full" :class="{ 'dark': theme === 'dark', 'light': theme === 'light' }" x-data="aiHubStudio(@js($boot))" x-init="init()" x-cloak>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brand['name'] ?? 'Laravel AI Hub' }}</title>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('ai_hub_theme');
                if (theme !== 'light' && theme !== 'dark') theme = 'dark';
                document.documentElement.classList.add(theme);
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink: { 950: '#070b12', 900: '#0c121c', 800: '#121a27', 700: '#1a2435', 600: '#233148' },
                        mist: { 100: '#e8eef7', 200: '#d0dbe9', 300: '#b6c2d6', 400: '#7b8aa3', 500: '#5c6b84' },
                        accent: { DEFAULT: '#2dd4bf', soft: '#5eead4', dim: '#14b8a6', hover: '#0d9488' },
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
        html.dark { color-scheme: dark; }
        html.light { color-scheme: light; }

        /* Dark mode theme */
        html.dark body {
            background:
                radial-gradient(ellipse 80% 50% at 10% -10%, rgba(45,212,191,.15), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 0%, rgba(56,189,248,.1), transparent 50%),
                #070b12;
            color: #f1f5f9;
        }
        html.dark .grid-bg {
            background-image: linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(ellipse 70% 55% at 50% 20%,#000,transparent);
        }
        html.dark .panel {
            background: rgba(18,26,39,.85);
            border: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(16px);
        }

        /* Light mode theme */
        html.light body {
            background:
                radial-gradient(ellipse 80% 50% at 10% -10%, rgba(45,212,191,.12), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 0%, rgba(56,189,248,.08), transparent 50%),
                #f8fafc;
            color: #0f172a;
        }
        html.light .grid-bg {
            background-image: linear-gradient(rgba(0,0,0,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,0,0,.03) 1px,transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(ellipse 70% 55% at 50% 20%,#000,transparent);
        }
        html.light .panel {
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(0,0,0,.08);
            backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px -2px rgba(0,0,0,.05);
        }
        html.dark select option { background: #0c121c; color: #f1f5f9; }
        html.light select option { background: #ffffff; color: #0f172a; }

        .scroll-thin::-webkit-scrollbar{width:6px;height:6px}
        .scroll-thin::-webkit-scrollbar-thumb{background:rgba(125,140,160,.35);border-radius:999px}
    </style>
</head>
<body class="min-h-full font-sans antialiased transition-colors duration-200">

<div class="pointer-events-none fixed inset-0 grid-bg" aria-hidden="true"></div>

{{-- Toast Notification --}}
<div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4"
     x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="pointer-events-auto flex items-center gap-2 max-w-lg rounded-xl border px-4 py-3 text-sm shadow-2xl backdrop-blur-xl"
         :class="toast.type==='error' ? (theme==='dark' ? 'border-rose-400/40 bg-rose-950/95 text-rose-100' : 'border-rose-300 bg-rose-50 text-rose-900 shadow-rose-200') : (theme==='dark' ? 'border-accent/40 bg-ink-800/95 text-mist-100 shadow-accent/10' : 'border-accent/60 bg-white/95 text-slate-800 shadow-slate-200')">
        <span x-show="toast.type!=='error'" class="flex h-2 w-2 rounded-full bg-accent animate-ping"></span>
        <span class="font-medium" x-text="toast.message"></span>
    </div>
</div>

<header class="relative border-b transition-colors duration-200 sticky top-0 z-40 backdrop-blur-xl"
        :class="theme==='dark' ? 'border-white/[0.06] bg-ink-900/70' : 'border-slate-200/80 bg-white/80'">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
        
        {{-- Logo & Brand --}}
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-emerald-500 text-ink-950 font-extrabold text-base shadow-lg shadow-accent/20">
                AI
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold tracking-tight sm:text-2xl" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">{{ $brand['name'] }}</h1>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                          :class="theme==='dark' ? 'bg-white/10 text-accent-soft' : 'bg-slate-100 text-teal-700 border border-teal-200'">Studio v1.3.0</span>
                </div>
                <p class="text-xs" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">{{ $brand['tagline'] }}</p>
            </div>
        </div>

        {{-- Nav Tabs & Theme Toggle --}}
        <div class="flex items-center gap-3">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 rounded-xl p-1 border transition-colors"
                 :class="theme==='dark' ? 'border-white/10 bg-ink-800/80' : 'border-slate-200 bg-slate-100/90'">
                <template x-for="tab in tabs" :key="tab.id">
                    <button type="button" @click="activeTab=tab.id; tab.id==='analytics' && loadAnalytics()"
                            class="rounded-lg px-3.5 py-1.5 text-xs font-semibold transition"
                            :class="activeTab===tab.id ? (theme==='dark' ? 'bg-accent/20 text-accent-soft shadow-sm' : 'bg-white text-teal-800 shadow-sm border border-slate-200/60') : (theme==='dark' ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')"
                            x-text="tab.label"></button>
                </template>
            </div>

            {{-- Light / Dark Mode Toggle --}}
            <button type="button" @click="toggleTheme()"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border transition hover:scale-105 active:scale-95"
                    :class="theme==='dark' ? 'border-white/10 bg-ink-800/80 text-amber-300 hover:bg-white/10' : 'border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    :aria-pressed="theme==='light'"
                    :title="theme==='dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                
                {{-- Sun Icon for Light Mode --}}
                <svg x-show="theme==='dark'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>

                {{-- Moon Icon for Dark Mode --}}
                <svg x-show="theme==='light'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
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
                    <h2 class="text-lg font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Provider Credentials & Models</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">
                        <span>Configured:</span>
                        <span class="inline-flex items-center gap-1.5 font-semibold" :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span x-text="savedProvidersCount() + ' of ' + providers.length + ' ready'"></span>
                        </span>
                        <span>·</span>
                        <span x-text="hasUnsavedChanges() ? '⚠️ Unsaved edits waiting to be saved' : '✓ All provider credentials synced'"></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs font-medium border px-3 py-2 rounded-xl cursor-pointer transition"
                           :class="theme==='dark' ? 'text-slate-300 bg-white/5 border-white/10 hover:bg-white/10' : 'text-slate-700 bg-slate-50 border-slate-200 hover:bg-slate-100'">
                        <input type="checkbox" x-model="failoverEnabled" class="rounded text-accent focus:ring-accent/30"
                               :class="theme==='dark' ? 'border-slate-600 bg-ink-900' : 'border-slate-300 bg-white'">
                        Automatic Failover
                    </label>

                    <button type="button" @click="saveSettings()" :disabled="busy || !hasUnsavedChanges()"
                            class="rounded-xl px-4 py-2 text-xs font-semibold transition flex items-center gap-2"
                            :class="hasUnsavedChanges() ? 'bg-accent text-ink-950 hover:bg-accent-soft shadow-lg shadow-accent/20 animate-pulse font-bold' : (theme==='dark' ? 'bg-white/10 text-slate-500 opacity-50 cursor-not-allowed' : 'bg-slate-200 text-slate-400 opacity-50 cursor-not-allowed')">
                        <span x-show="!busy">Save All Changes</span>
                        <span x-show="busy">Saving…</span>
                    </button>
                </div>
            </div>

            {{-- Default Provider Selection Pills --}}
            <div class="mt-6 border-t pt-5" :class="theme==='dark' ? 'border-white/10' : 'border-slate-200'">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Active Default Provider (Tried #1)</p>
                    <span class="text-[11px]" :class="theme==='dark' ? 'text-slate-500' : 'text-slate-400'">Click any card to set primary default</span>
                </div>

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach($boot['providers'] as $p)
                        <button type="button" @click="setDefaultProvider('{{ $p }}')"
                                class="group relative rounded-xl border p-3 text-left transition flex flex-col justify-between"
                                :class="defaultProvider==='{{ $p }}' ? (theme==='dark' ? 'border-accent/60 bg-accent/15 ring-1 ring-accent/40' : 'border-teal-500 bg-teal-50 ring-1 ring-teal-500/40') : (form['{{ $p }}']?.has_key ? (theme==='dark' ? 'border-white/10 bg-ink-900/70 hover:border-white/20' : 'border-slate-200 bg-white hover:border-slate-300') : (theme==='dark' ? 'border-white/5 bg-ink-950/40 opacity-70 hover:opacity-100 hover:border-white/10' : 'border-slate-100 bg-slate-50/60 opacity-70 hover:opacity-100 hover:border-slate-200'))">
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">{{ $boot['labels'][$p] ?? ucfirst($p) }}</span>
                                
                                {{-- Status Dot in Pill --}}
                                <div class="flex items-center gap-1">
                                    <template x-if="isDirty('{{ $p }}')">
                                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-ping" title="Unsaved changes"></span>
                                    </template>
                                    <template x-if="!isDirty('{{ $p }}') && form['{{ $p }}']?.has_key">
                                        <span class="h-2 w-2 rounded-full bg-emerald-400" title="Saved & Active"></span>
                                    </template>
                                    <template x-if="!isDirty('{{ $p }}') && !form['{{ $p }}']?.has_key">
                                        <span class="h-2 w-2 rounded-full" :class="theme==='dark' ? 'bg-slate-600' : 'bg-slate-300'" title="No API key saved"></span>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[10px]">
                                <span class="truncate font-mono" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'" x-text="resolvedModel('{{ $p }}')"></span>
                                <template x-if="defaultProvider==='{{ $p }}'">
                                    <span class="shrink-0 font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'">★ Default</span>
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
                         'ring-1 ring-accent/50 border-accent/50': defaultProvider==='{{ $p }}',
                         'ring-1 ring-amber-500/50 border-amber-500/50 shadow-lg shadow-amber-500/5': isDirty('{{ $p }}')
                     }">
                    
                    {{-- Card Header --}}
                    <div>
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold shadow-inner"
                                      :class="form['{{ $p }}']?.has_key ? (theme==='dark' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-emerald-100 text-emerald-800 border border-emerald-300') : (theme==='dark' ? 'bg-white/5 text-slate-400 border border-white/10' : 'bg-slate-100 text-slate-500 border border-slate-200')">
                                    {{ strtoupper(substr($boot['labels'][$p] ?? $p, 0, 2)) }}
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-base" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">{{ $boot['labels'][$p] ?? ucfirst($p) }}</h3>
                                        <template x-if="defaultProvider==='{{ $p }}'">
                                            <span class="rounded px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider"
                                                  :class="theme==='dark' ? 'bg-accent/20 border border-accent/40 text-accent-soft' : 'bg-teal-100 border border-teal-300 text-teal-800'">Default</span>
                                        </template>
                                    </div>

                                    {{-- Status Badge --}}
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <template x-if="isDirty('{{ $p }}')">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                                  :class="theme==='dark' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-amber-50 text-amber-800 border-amber-300'">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                Unsaved edits
                                            </span>
                                        </template>
                                        <template x-if="!isDirty('{{ $p }}') && form['{{ $p }}']?.has_key">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                  :class="theme==='dark' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                Saved & ready
                                            </span>
                                        </template>
                                        <template x-if="!isDirty('{{ $p }}') && !form['{{ $p }}']?.has_key">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                  :class="theme==='dark' ? 'bg-slate-800 text-slate-400 border border-white/10' : 'bg-slate-100 text-slate-500 border border-slate-200'">
                                                No key added
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-1.5 text-xs cursor-pointer" :class="theme==='dark' ? 'text-slate-300' : 'text-slate-600'" title="Enable/Disable this provider">
                                    <input type="checkbox" x-model="form['{{ $p }}'].enabled" class="rounded text-accent"
                                           :class="theme==='dark' ? 'border-slate-600 bg-ink-900' : 'border-slate-300 bg-white'"
                                           @change="checkDirty('{{ $p }}')">
                                    <span class="text-[11px]" x-text="form['{{ $p }}'].enabled ? 'On' : 'Off'"></span>
                                </label>
                                <button type="button" @click="testProvider('{{ $p }}')" :disabled="busy"
                                        class="rounded-lg border px-2.5 py-1 text-xs transition disabled:opacity-40"
                                        :class="theme==='dark' ? 'border-white/10 text-slate-300 hover:bg-white/10 hover:text-white' : 'border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900'">
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- API Key Input --}}
                        <div class="mb-3.5">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">API Key</span>
                                <template x-if="form['{{ $p }}']?.has_key">
                                    <span class="text-[10px] text-emerald-400 font-mono">✓ Saved securely</span>
                                </template>
                            </div>
                            <input type="password" x-model="form['{{ $p }}'].api_key"
                                   @input="checkDirty('{{ $p }}')"
                                   :placeholder="form['{{ $p }}']?.has_key ? '•••••••• saved (type new to replace)' : 'Enter {{ $boot['labels'][$p] ?? ucfirst($p) }} API key'"
                                   class="w-full rounded-xl border px-3 py-2.5 text-sm placeholder:text-slate-400 focus:border-accent/60 focus:outline-none focus:ring-2 focus:ring-accent/20 transition"
                                   :class="form['{{ $p }}'].api_key ? 'border-accent/50 bg-accent/5' : (theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900')">
                        </div>

                        {{-- Model Selection --}}
                        <div class="mb-4">
                            <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Active Model</span>
                            <div class="flex flex-col gap-2">
                                <select x-model="form['{{ $p }}'].modelSelect"
                                        @change="checkDirty('{{ $p }}')"
                                        class="w-full rounded-xl border px-3 py-2.5 text-sm focus:border-accent/60 focus:outline-none focus:ring-2 focus:ring-accent/20 transition"
                                        :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                                    @foreach(($boot['popular'][$p] ?? []) as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                    <option value="__custom">Custom Model ID…</option>
                                </select>
                                <input type="text" x-show="form['{{ $p }}'].modelSelect==='__custom'"
                                       x-model="form['{{ $p }}'].customModel"
                                       @input="checkDirty('{{ $p }}')"
                                       placeholder="e.g. {{ $boot['popular'][$p][0] ?? 'custom-model-id' }}"
                                       class="w-full rounded-xl border px-3 py-2.5 font-mono text-sm focus:border-accent/60 focus:outline-none focus:ring-2 focus:ring-accent/20"
                                       :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                            </div>
                        </div>
                    </div>

                    {{-- Dedicated Per-Provider Action / Save Bar Beside User --}}
                    <div class="border-t pt-3 flex items-center justify-between gap-2" :class="theme==='dark' ? 'border-white/10' : 'border-slate-200'">
                        <div class="text-[11px]">
                            <template x-if="isDirty('{{ $p }}')">
                                <span class="text-amber-400 font-semibold">Modified · click save</span>
                            </template>
                            <template x-if="!isDirty('{{ $p }}') && defaultProvider!=='{{ $p }}'">
                                <button type="button" @click="setDefaultProvider('{{ $p }}')"
                                        class="transition text-[11px] underline"
                                        :class="theme==='dark' ? 'text-slate-400 hover:text-accent-soft' : 'text-slate-500 hover:text-teal-700'">
                                    Set as default
                                </button>
                            </template>
                            <template x-if="!isDirty('{{ $p }}') && defaultProvider==='{{ $p }}'">
                                <span class="font-bold text-[11px]" :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'">Current default</span>
                            </template>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <template x-if="isDirty('{{ $p }}')">
                                <button type="button" @click="revertProvider('{{ $p }}')"
                                        class="rounded-lg border px-2.5 py-1.5 text-xs transition"
                                        :class="theme==='dark' ? 'border-white/10 text-slate-400 hover:text-white hover:bg-white/5' : 'border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100'">
                                    Revert
                                </button>
                            </template>

                            {{-- Direct Save Button Beside User on the Card --}}
                            <button type="button" @click="saveSingleProvider('{{ $p }}')" :disabled="busy || !isDirty('{{ $p }}')"
                                    class="rounded-lg px-3.5 py-1.5 text-xs font-semibold transition flex items-center gap-1.5"
                                    :class="isDirty('{{ $p }}') ? 'bg-accent text-ink-950 hover:bg-accent-soft shadow-md shadow-accent/20 font-bold' : (theme==='dark' ? 'bg-white/5 text-slate-500 opacity-50 cursor-not-allowed' : 'bg-slate-100 text-slate-400 opacity-50 cursor-not-allowed')">
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
            <div class="text-sm" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-600'">
                <span class="font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Save all providers in one click:</span>
                Stores API keys, model selections, and default priorities to application storage.
            </div>
            <button type="button" @click="saveSettings()" :disabled="busy"
                    class="rounded-xl bg-accent px-6 py-3 text-sm font-bold text-ink-950 hover:bg-accent-soft disabled:opacity-40 shadow-lg shadow-accent/15 transition">
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
                    <h2 class="text-lg font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Failover Priority Chain</h2>
                    <p class="mt-1 text-sm" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">#1 is attempted first. If rate-limited or unavailable, AI Hub fails over down the chain.</p>
                </div>
                <label class="flex items-center gap-2 rounded-xl border px-3 py-2 text-sm"
                       :class="theme==='dark' ? 'border-white/10 bg-white/5 text-slate-300' : 'border-slate-200 bg-slate-50 text-slate-700'">
                    <input type="checkbox" x-model="failoverEnabled" class="rounded text-accent focus:ring-accent/30"
                           :class="theme==='dark' ? 'border-slate-600 bg-ink-900' : 'border-slate-300 bg-white'">
                    Failover enabled
                </label>
            </div>

            <div class="mt-6 space-y-3">
                <template x-for="(p, index) in priority" :key="'pri-'+p">
                    <div class="flex items-center gap-3 rounded-2xl border p-4 transition"
                         :class="theme==='dark' ? 'border-white/10 bg-ink-950/60 hover:border-accent/30' : 'border-slate-200 bg-white hover:border-teal-400 shadow-sm'">
                        
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-mono text-sm font-bold shadow-inner"
                             :class="index===0 ? 'bg-accent text-ink-950 shadow-accent/20' : (theme==='dark' ? 'bg-white/5 text-slate-400 border border-white/10' : 'bg-slate-100 text-slate-600 border border-slate-200')"
                             x-text="'#'+(index+1)"></div>
                        
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'" x-text="labels[p]"></p>
                                <template x-if="form[p]?.has_key">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.2 text-[9px] font-medium"
                                          :class="theme==='dark' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Saved
                                    </span>
                                </template>
                                <template x-if="!form[p]?.has_key">
                                    <span class="rounded px-2 py-0.2 text-[9px]"
                                          :class="theme==='dark' ? 'bg-slate-800 text-slate-400 border border-white/5' : 'bg-slate-100 text-slate-500 border border-slate-200'">No key</span>
                                </template>
                            </div>
                            <p class="truncate font-mono text-[11px] mt-0.5" :class="theme==='dark' ? 'text-slate-500' : 'text-slate-400'">
                                <span x-text="resolvedModel(p)"></span>
                                · <span x-text="form[p]?.enabled ? 'enabled' : 'disabled'"></span>
                            </p>
                        </div>
                        
                        <div class="flex shrink-0 gap-1.5">
                            <button type="button" @click="movePriority(index,-1)" :disabled="index===0"
                                    class="rounded-lg border px-3 py-1.5 text-xs transition disabled:opacity-30"
                                    :class="theme==='dark' ? 'border-white/10 text-slate-300 hover:bg-white/10' : 'border-slate-200 text-slate-700 hover:bg-slate-100'">↑</button>
                            <button type="button" @click="movePriority(index,1)" :disabled="index===priority.length-1"
                                    class="rounded-lg border px-3 py-1.5 text-xs transition disabled:opacity-30"
                                    :class="theme==='dark' ? 'border-white/10 text-slate-300 hover:bg-white/10' : 'border-slate-200 text-slate-700 hover:bg-slate-100'">↓</button>
                            <button type="button" @click="makeFirst(index)"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                                    :class="theme==='dark' ? 'border-accent/30 text-accent-soft hover:bg-accent/15' : 'border-teal-300 text-teal-700 hover:bg-teal-50'">Make #1</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-6 rounded-xl border px-4 py-3.5 font-mono text-xs flex items-center gap-2 overflow-x-auto"
                 :class="theme==='dark' ? 'border-white/10 bg-ink-900/70 text-slate-400' : 'border-slate-200 bg-slate-50 text-slate-600'">
                <span class="font-bold shrink-0" :class="theme==='dark' ? 'text-slate-500' : 'text-slate-400'">Chain:</span>
                <span class="font-medium whitespace-nowrap" :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'" x-text="priority.map((p,i)=> (i+1)+'. '+labels[p]).join('  →  ')"></span>
            </div>

            <button type="button" @click="savePriority()" :disabled="busy"
                    class="mt-6 rounded-xl bg-accent px-6 py-3 text-sm font-bold text-ink-950 hover:bg-accent-soft disabled:opacity-40 shadow-lg shadow-accent/15">
                Save Priority Chain
            </button>
        </div>
    </section>

    {{-- ==================== PLAYGROUND ==================== --}}
    <section x-show="activeTab==='playground'" class="space-y-6">
        <div class="panel rounded-2xl p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Playground</h2>
                    <p class="mt-1 text-sm" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Run a live prompt against any configured provider. Failover is off so you test the provider you pick.</p>
                </div>
                <select x-model="playground.template" @change="loadPromptTemplate()"
                        class="rounded-xl border px-3 py-2 text-sm"
                        :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                    <option value="">Load template…</option>
                    <template x-for="tpl in prompts" :key="tpl.name">
                        <option :value="tpl.name" x-text="tpl.name"></option>
                    </template>
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-4">
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Provider</span>
                    <select x-model="playground.provider" @change="playground.model = (popular[playground.provider] || [])[0] || ''; persistPlayground()"
                            class="w-full rounded-xl border px-3 py-2.5 text-sm"
                            :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                        <template x-for="p in providers" :key="p">
                            <option :value="p" x-text="labels[p] || p"></option>
                        </template>
                    </select>
                </label>
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Model</span>
                    <select x-model="playground.model" @change="persistPlayground()"
                            class="w-full rounded-xl border px-3 py-2.5 text-sm"
                            :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                        <template x-for="m in (popular[playground.provider] || [])" :key="m">
                            <option :value="m" x-text="m"></option>
                        </template>
                        <option value="__custom">Custom Model ID…</option>
                    </select>
                    <input x-show="playground.model==='__custom'" x-model="playground.customModel" @input="persistPlayground()"
                           placeholder="custom-model-id" class="mt-2 w-full rounded-xl border px-3 py-2 font-mono text-sm"
                           :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                </label>
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Temperature</span>
                    <input type="number" min="0" max="2" step="0.1" x-model.number="playground.temperature" @input="persistPlayground()"
                           class="w-full rounded-xl border px-3 py-2.5 text-sm"
                           :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                </label>
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Max tokens</span>
                    <input type="number" min="16" max="128000" x-model.number="playground.maxTokens" @input="persistPlayground()"
                           class="w-full rounded-xl border px-3 py-2.5 text-sm"
                           :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                </label>
            </div>

            <label class="block text-xs font-semibold mb-3">
                <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">System prompt (optional)</span>
                <textarea x-model="playground.system" @input="persistPlayground()" rows="2"
                          class="w-full rounded-xl border px-3 py-2.5 text-sm"
                          :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'"
                          placeholder="You are a helpful assistant."></textarea>
            </label>
            <label class="block text-xs font-semibold mb-3">
                <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">User message</span>
                <textarea x-model="playground.prompt" @input="persistPlayground()" rows="5"
                          class="w-full rounded-xl border px-3 py-2.5 text-sm"
                          :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'"
                          placeholder="Ask anything…"></textarea>
            </label>
            <label class="block text-xs font-semibold mb-4">
                <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Image URL (optional vision)</span>
                <input type="url" x-model="playground.image" @input="persistPlayground()"
                       placeholder="https://example.com/photo.jpg"
                       class="w-full rounded-xl border px-3 py-2.5 text-sm"
                       :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
            </label>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="runPlayground(false)" :disabled="busy || !playground.prompt"
                        class="rounded-xl bg-accent px-5 py-2.5 text-sm font-bold text-ink-950 hover:bg-accent-soft disabled:opacity-40">
                    <span x-show="!busy">Send</span>
                    <span x-show="busy">Running…</span>
                </button>
                <button type="button" @click="runPlayground(true)" :disabled="busy || !playground.prompt"
                        class="rounded-xl border px-5 py-2.5 text-sm font-semibold disabled:opacity-40"
                        :class="theme==='dark' ? 'border-white/10 text-slate-200 hover:bg-white/10' : 'border-slate-200 text-slate-700 hover:bg-slate-100'">
                    Stream
                </button>
                <button type="button" @click="saveCurrentAsTemplate()" :disabled="busy || !playground.prompt"
                        class="rounded-xl border px-4 py-2.5 text-xs font-semibold disabled:opacity-40"
                        :class="theme==='dark' ? 'border-white/10 text-slate-300 hover:bg-white/10' : 'border-slate-200 text-slate-600 hover:bg-slate-100'">
                    Save as template
                </button>
            </div>
        </div>

        <div class="panel rounded-2xl p-5 sm:p-6 min-h-[180px]">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Reply</h3>
                <p class="text-[11px] font-mono" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'"
                   x-show="playground.meta"
                   x-text="playground.meta ? (playground.meta.provider+' · '+playground.meta.model+' · '+(playground.meta.latency_ms||0)+'ms · $'+(Number(playground.meta.cost_usd||0).toFixed(4))+' · '+(playground.meta.total_tokens||0)+' tok') : ''"></p>
            </div>
            <pre class="whitespace-pre-wrap text-sm leading-relaxed font-sans" :class="theme==='dark' ? 'text-slate-200' : 'text-slate-800'"
                 x-text="playground.reply || 'Send a prompt to see the model reply here.'"></pre>
        </div>
    </section>

    {{-- ==================== ANALYTICS ==================== --}}
    <section x-show="activeTab==='analytics'" class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Usage Analytics</h2>
                <p class="mt-1 text-sm" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Past 30 days telemetry: cost, latency, failure rates & token volumes</p>
            </div>
            <button type="button" @click="loadAnalytics()" class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition"
                    :class="theme==='dark' ? 'border-white/10 text-slate-300 hover:bg-white/10' : 'border-slate-200 text-slate-700 hover:bg-slate-100'">Refresh</button>
        </div>

        <div class="panel rounded-2xl p-5"
             :class="budget.breached ? (theme==='dark' ? 'ring-1 ring-amber-400/40' : 'ring-1 ring-amber-400') : ''">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Spend budget</h3>
                    <p class="mt-1 text-xs" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">
                        This month: <span class="font-mono" x-text="'$'+(Number(budget.month_spend||0).toFixed(4))"></span>
                        <template x-if="budget.monthly_usd">
                            <span> / <span class="font-mono" x-text="'$'+Number(budget.monthly_usd).toFixed(2)"></span> remaining <span class="font-mono" x-text="'$'+Number(budget.remaining||0).toFixed(4)"></span></span>
                        </template>
                    </p>
                    <p x-show="budget.breached" class="mt-1 text-xs font-semibold text-amber-400" x-text="(budget.warnings||[]).map(w => w.message).join(' ')"></p>
                </div>
                <button type="button" @click="saveBudget()" :disabled="busy"
                        class="rounded-xl bg-accent px-4 py-2 text-xs font-bold text-ink-950 hover:bg-accent-soft disabled:opacity-40">Save budget</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Monthly USD cap</span>
                    <input type="number" min="0" step="0.01" x-model.number="budgetForm.monthly_usd"
                           placeholder="Unlimited"
                           class="w-full rounded-xl border px-3 py-2 text-sm"
                           :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                </label>
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">When exceeded</span>
                    <select x-model="budgetForm.on_exceed"
                            class="w-full rounded-xl border px-3 py-2 text-sm"
                            :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                        <option value="block">Block requests</option>
                        <option value="warn">Warn only</option>
                    </select>
                </label>
                <label class="text-xs font-semibold">
                    <span class="mb-1.5 block uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Job caps (job=usd)</span>
                    <input type="text" x-model="budgetForm.jobCaps" placeholder="invoice-ocr=5, chat=20"
                           class="w-full rounded-xl border px-3 py-2 text-sm"
                           :class="theme==='dark' ? 'border-white/10 bg-ink-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-900'">
                </label>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Total Spend (30d)</p>
                <p class="mt-2 text-2xl font-extrabold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'" x-text="'$'+(analytics.summary?.total_cost_usd ?? 0).toFixed(4)"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.requests ?? 0)+' total requests'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Failure Rate</p>
                <p class="mt-2 text-2xl font-extrabold" :class="(analytics.summary?.failure_rate||0)>5?'text-rose-400':'text-emerald-400'"
                   x-text="(analytics.summary?.failure_rate ?? 0)+'%'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.failures ?? 0)+' failed attempts'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">JSON Recovery</p>
                <p class="mt-2 text-2xl font-extrabold text-amber-400" x-text="(analytics.summary?.json_recovery_rate ?? 0)+'%'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(analytics.summary?.json_recovered ?? 0)+' auto-repaired'"></p>
            </div>
            <div class="panel rounded-2xl p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider" :class="theme==='dark' ? 'text-slate-400' : 'text-slate-500'">Latency (p95)</p>
                <p class="mt-2 text-2xl font-extrabold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'" x-text="(analytics.latency?.p95 ?? 0)+' ms'"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="'p50: '+(analytics.latency?.p50 ?? 0)+'ms · p99: '+(analytics.latency?.p99 ?? 0)+'ms'"></p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="panel rounded-2xl p-5">
                <h3 class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Spend by Provider</h3>
                <ul class="mt-4 space-y-3.5">
                    <template x-if="!(analytics.cost_by_provider||[]).length">
                        <li class="text-sm text-slate-500">No requests recorded yet.</li>
                    </template>
                    <template x-for="row in (analytics.cost_by_provider||[])" :key="row.provider">
                        <li>
                            <div class="mb-1.5 flex justify-between text-sm">
                                <span class="font-medium" :class="theme==='dark' ? 'text-slate-200' : 'text-slate-800'" x-text="labels[row.provider]||row.provider"></span>
                <span class="font-mono font-bold" :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'" x-text="'$'+Number(row.cost||0).toFixed(4)"></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full" :class="theme==='dark' ? 'bg-white/5' : 'bg-slate-200'">
                                <div class="h-full rounded-full bg-gradient-to-r from-accent to-emerald-400" :style="'width:'+costBar(row.cost)+'%'"></div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="panel rounded-2xl p-5">
                <h3 class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Top Tracked Jobs</h3>
                <ul class="mt-4 max-h-64 space-y-2 overflow-y-auto scroll-thin">
                    <template x-if="!(analytics.top_jobs||[]).length">
                        <li class="text-sm text-slate-500">No job traces logged. Use <code class="px-1 rounded" :class="theme==='dark' ? 'text-accent-soft bg-white/5' : 'text-teal-700 bg-slate-100'">->forJob('invoice')</code>.</li>
                    </template>
                    <template x-for="job in (analytics.top_jobs||[])" :key="job.job">
                        <li class="flex items-center justify-between rounded-xl border px-3.5 py-2.5"
                            :class="theme==='dark' ? 'border-white/5 bg-white/[0.02]' : 'border-slate-200 bg-slate-50'">
                            <span class="truncate font-mono text-xs" :class="theme==='dark' ? 'text-slate-300' : 'text-slate-700'" x-text="job.job"></span>
                            <span class="shrink-0 font-mono text-[11px] text-slate-400" x-text="job.tokens+' tokens'"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="panel rounded-2xl p-5">
            <h3 class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">Daily Cost Trend</h3>
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
    <div class="flex items-center gap-4 rounded-2xl border px-5 py-3.5 shadow-2xl backdrop-blur-2xl"
         :class="theme==='dark' ? 'border-accent/40 bg-ink-900/95 shadow-accent/15' : 'border-teal-400 bg-white/95 shadow-slate-300'">
        <div class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-ping"></span>
            <span class="text-sm font-bold" :class="theme==='dark' ? 'text-white' : 'text-slate-900'">
                Unsaved changes in:
                <span :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'" x-text="dirtyProviders().map(p => labels[p] || p).join(', ')"></span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="revertAll()"
                    class="rounded-xl border px-3 py-1.5 text-xs transition"
                    :class="theme==='dark' ? 'border-white/10 text-slate-300 hover:bg-white/10' : 'border-slate-200 text-slate-700 hover:bg-slate-100'">
                Discard
            </button>
            <button type="button" @click="saveSettings()" :disabled="busy"
                    class="rounded-xl bg-accent px-4 py-1.5 text-xs font-bold text-ink-950 hover:bg-accent-soft shadow-md shadow-accent/20 transition">
                Save All
            </button>
        </div>
    </div>
</div>

<footer class="relative border-t py-8 text-center text-xs transition-colors"
        :class="theme==='dark' ? 'border-white/[0.05] text-slate-500' : 'border-slate-200 text-slate-500'">
    Laravel AI Hub · Developed by
    <a href="https://imrandev.bd/" target="_blank" class="font-medium hover:underline" :class="theme==='dark' ? 'text-accent-soft' : 'text-teal-700'">Imran Dev BD</a>
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
        theme: localStorage.getItem('ai_hub_theme') || 'dark',
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
            { id: 'playground', label: 'Playground' },
            { id: 'priority', label: 'Priority Chain' },
            { id: 'analytics', label: 'Usage Analytics' },
        ],
        busy: false,
        toast: { show: false, message: '', type: 'ok' },
        analytics: { summary: {}, cost_by_provider: [], latency: {}, top_jobs: [], daily: [] },
        prompts: boot.prompts || [],
        budget: boot.budget || { month_spend: 0, monthly_usd: null, remaining: null, on_exceed: 'block', breached: false, warnings: [] },
        budgetForm: {
            monthly_usd: boot.budget?.monthly_usd || '',
            on_exceed: boot.budget?.on_exceed || 'block',
            jobCaps: Object.entries(boot.budget?.per_job || {}).map(([k,v]) => k+'='+v).join(', '),
        },
        playground: Object.assign({
            provider: settings.default || providers[0],
            model: (popular[settings.default || providers[0]] || [])[0] || '',
            customModel: '',
            temperature: 0.7,
            maxTokens: 1024,
            system: '',
            prompt: '',
            image: '',
            template: '',
            reply: '',
            meta: null,
        }, (function () { try { return JSON.parse(localStorage.getItem('ai_hub_playground') || '{}'); } catch (e) { return {}; } })()),
        routes: {
            settings: @json(route('ai-hub.api.settings')),
            provider: @json(route('ai-hub.api.provider')),
            priority: @json(route('ai-hub.api.priority')),
            test: @json(route('ai-hub.api.test')),
            analytics: @json(route('ai-hub.api.analytics')),
            playground: @json(route('ai-hub.api.playground')),
            playgroundStream: @json(route('ai-hub.api.playground.stream')),
            budget: @json(route('ai-hub.api.budget')),
            prompts: @json(route('ai-hub.api.prompts')),
        },

        init() {
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(this.theme);
            if (!this.playground.provider) this.playground.provider = this.defaultProvider || this.providers[0];
            if (!this.playground.model) this.playground.model = (this.popular[this.playground.provider] || [])[0] || '';
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('ai_hub_theme', this.theme);
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(this.theme);
        },

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
                if (data.budget) {
                    this.budget = data.budget;
                    this.budgetForm.monthly_usd = data.budget.monthly_usd || '';
                    this.budgetForm.on_exceed = data.budget.on_exceed || 'block';
                    this.budgetForm.jobCaps = Object.entries(data.budget.per_job || {}).map(([k,v]) => k+'='+v).join(', ');
                }
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
                if (!this.form[p]) {
                    this.form[p] = { api_key: '', has_key: false, enabled: true, modelSelect: '', customModel: '', _dirty: false };
                }
                
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
            if (boot.prompts) this.prompts = boot.prompts;
            if (boot.budget) this.budget = boot.budget;
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

        persistPlayground() {
            const { reply, meta, template, ...draft } = this.playground;
            localStorage.setItem('ai_hub_playground', JSON.stringify(draft));
        },

        playgroundModel() {
            return this.playground.model === '__custom' ? this.playground.customModel : this.playground.model;
        },

        playgroundPayload() {
            return {
                provider: this.playground.provider,
                model: this.playgroundModel() || null,
                system: this.playground.system || null,
                prompt: this.playground.prompt,
                image: this.playground.image || null,
                temperature: this.playground.temperature,
                max_tokens: this.playground.maxTokens,
            };
        },

        async runPlayground(stream) {
            if (!this.playground.prompt) return;
            this.busy = true;
            this.playground.reply = stream ? '' : '…';
            this.playground.meta = null;
            this.persistPlayground();
            try {
                if (stream) {
                    await this.streamPlayground();
                } else {
                    const data = await this.request(this.routes.playground, this.playgroundPayload());
                    this.playground.reply = data.content || '';
                    this.playground.meta = data;
                }
            } catch (e) {
                this.playground.reply = '';
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        async streamPlayground() {
            const res = await fetch(this.routes.playgroundStream, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(this.playgroundPayload()),
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || 'Stream failed');
            }
            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            let buf = '';
            this.playground.reply = '';
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buf += decoder.decode(value, { stream: true });
                const parts = buf.split('\n\n');
                buf = parts.pop();
                for (const part of parts) {
                    const line = part.split('\n').find(l => l.startsWith('data:'));
                    if (!line) continue;
                    const evt = JSON.parse(line.slice(5).trim() || '{}');
                    if (evt.error) throw new Error(evt.error);
                    if (evt.chunk) this.playground.reply += evt.chunk;
                    if (evt.meta) this.playground.meta = Object.assign({}, this.playground.meta || {}, evt.meta);
                    if (evt.done) this.playground.meta = Object.assign({}, this.playground.meta || {});
                }
            }
        },

        loadPromptTemplate() {
            const tpl = (this.prompts || []).find(p => p.name === this.playground.template);
            if (!tpl) return;
            if (tpl.provider) this.playground.provider = tpl.provider;
            if (tpl.model) {
                const list = this.popular[tpl.provider] || [];
                this.playground.model = list.includes(tpl.model) ? tpl.model : '__custom';
                this.playground.customModel = list.includes(tpl.model) ? '' : tpl.model;
            }
            this.playground.system = tpl.system || '';
            this.playground.prompt = tpl.user || '';
            this.persistPlayground();
        },

        async saveCurrentAsTemplate() {
            const name = window.prompt('Template name', this.playground.template || 'untitled');
            if (!name) return;
            const next = (this.prompts || []).filter(p => p.name !== name);
            next.push({
                name,
                provider: this.playground.provider,
                model: this.playgroundModel(),
                system: this.playground.system,
                user: this.playground.prompt,
            });
            try {
                const data = await this.request(this.routes.prompts, { prompts: next });
                this.prompts = data.data?.prompts || next;
                this.playground.template = name;
                this.notify(data.message || 'Template saved.');
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },

        parseJobCaps() {
            const out = {};
            String(this.budgetForm.jobCaps || '').split(',').forEach(part => {
                const [k, v] = part.split('=').map(s => s && s.trim());
                if (k && v && !Number.isNaN(Number(v))) out[k] = Number(v);
            });
            return out;
        },

        async saveBudget() {
            this.busy = true;
            try {
                const data = await this.request(this.routes.budget, {
                    budget: {
                        monthly_usd: this.budgetForm.monthly_usd === '' ? null : Number(this.budgetForm.monthly_usd),
                        on_exceed: this.budgetForm.on_exceed || 'block',
                        per_provider: this.budget.per_provider || {},
                        per_job: this.parseJobCaps(),
                    },
                });
                this.budget = data.budget || this.budget;
                if (data.data) this.applyBoot(data.data);
                this.notify(data.message || 'Budget saved.');
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
</body>
</html>
