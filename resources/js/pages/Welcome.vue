<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import heroIllustration from '@/assets/illustrations/civic-dialogue.svg';
import dashboardIllustration from '@/assets/illustrations/democracy-dashboard.svg';
import { ArrowRight, CheckCircle, FileText, Globe, MessageSquare, Phone, Smartphone, Users } from 'lucide-vue-next';
</script>

<template>
    <Head title="Huduma Ya Raia - Your Voice in Parliament">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.08),_transparent_55%)]"></div>

        <header class="sticky top-0 z-40 border-b border-emerald-100/80 bg-white/80 backdrop-blur">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 md:px-6">
                <AppLogo />
                <nav class="hidden items-center gap-6 text-sm font-medium text-emerald-800 md:flex">
                    <a href="#features" class="transition hover:text-emerald-600">Features</a>
                    <a href="#how-it-works" class="transition hover:text-emerald-600">How it works</a>
                    <a href="#personas" class="transition hover:text-emerald-600">Roles</a>
                    <a href="#channels" class="transition hover:text-emerald-600">Channels</a>
                </nav>
                <div class="hidden items-center gap-3 md:flex">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:border-emerald-600 hover:text-emerald-900"
                    >
                        Go to dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex items-center rounded-full border border-transparent px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:text-emerald-900"
                        >
                            Ingia / Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            Jisajili / Register
                        </Link>
                    </template>
                </div>
                <div class="md:hidden">
                    <Link :href="$page.props.auth.user ? dashboard() : login()" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        {{ $page.props.auth.user ? 'Dashboard' : 'Ingia' }}
                    </Link>
                </div>
            </div>
        </header>

        <main class="relative flex-1">
            <section class="mx-auto flex w-full max-w-7xl flex-col items-center gap-12 px-4 py-16 md:flex-row md:px-6">
                <div class="flex flex-1 flex-col items-start gap-6 text-center md:text-left">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-4 py-1.5 text-sm font-semibold text-emerald-700 shadow-sm">
                        🇰🇪 Huduma Ya Raia — Kenya's digital participation desk
                    </span>
                    <h1 class="text-4xl font-bold leading-tight text-emerald-900 md:text-5xl">
                        Sauti yako, <span class="text-emerald-600">sheria yetu.</span>
                    </h1>
                    <p class="max-w-2xl text-lg text-emerald-800/80">
                        Review plain-language bill summaries, contribute clause-by-clause feedback, and follow how your insights inform parliamentary debate from committee rooms to the floor of the House.
                    </p>
                    <div class="flex w-full flex-col gap-4 sm:flex-row">
                        <Link
                            :href="register()"
                            class="inline-flex w-full items-center justify-center rounded-full bg-emerald-600 px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-emerald-700 sm:w-auto"
                        >
                            Anza kushiriki • Start participating
                            <ArrowRight class="ml-2 h-5 w-5" />
                        </Link>
                        <a href="#how-it-works" class="inline-flex w-full items-center justify-center rounded-full border border-emerald-300 px-6 py-3 text-base font-semibold text-emerald-700 transition hover:border-emerald-500 hover:text-emerald-900 sm:w-auto">
                            Jifunze zaidi / Learn more
                        </a>
                    </div>
                    <div class="grid w-full gap-6 text-left sm:grid-cols-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100"><Users class="h-5 w-5 text-emerald-700" /></div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-900">47 counties</p>
                                <p class="text-xs text-emerald-800/70">Nationwide coverage</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100"><MessageSquare class="h-5 w-5 text-emerald-700" /></div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-900">Clause-level input</p>
                                <p class="text-xs text-emerald-800/70">Structured participation</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100"><CheckCircle class="h-5 w-5 text-emerald-700" /></div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-900">Real-time tracking</p>
                                <p class="text-xs text-emerald-800/70">Transparent outcomes</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <div class="absolute inset-0 max-h-[28rem] max-w-[28rem] animate-pulse rounded-[32px] bg-emerald-200/40 blur-3xl" aria-hidden="true"></div>
                    <img :src="heroIllustration" alt="Citizens collaborating" class="relative w-full max-w-lg rounded-[36px] border-4 border-white shadow-2xl" />
                </div>
            </section>

            <section id="features" class="mx-auto w-full max-w-7xl px-4 py-16 md:px-6">
                <div class="grid gap-6 md:grid-cols-3">
                    <article class="rounded-2xl border border-emerald-100/80 bg-white/90 p-6 shadow-sm backdrop-blur">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                            <FileText class="h-6 w-6 text-emerald-700" />
                        </div>
                        <h3 class="text-xl font-semibold text-emerald-900">Soma mswada</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">
                            Access bilingual clauses, AI-assisted highlights, and committee context curated for citizens.
                        </p>
                    </article>
                    <article class="rounded-2xl border border-emerald-100/80 bg-white/90 p-6 shadow-sm backdrop-blur">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                            <MessageSquare class="h-6 w-6 text-emerald-700" />
                        </div>
                        <h3 class="text-xl font-semibold text-emerald-900">Toa maoni</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">
                            Submit structured views, attach supporting evidence, and collaborate with community organisations.
                        </p>
                    </article>
                    <article class="rounded-2xl border border-emerald-100/80 bg-white/90 p-6 shadow-sm backdrop-blur">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                            <CheckCircle class="h-6 w-6 text-emerald-700" />
                        </div>
                        <h3 class="text-xl font-semibold text-emerald-900">Fuatilia matokeo</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">
                            Receive confirmations, see committee responses, and follow implementation dashboards.
                        </p>
                    </article>
                </div>
            </section>

            <section id="how-it-works" class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 md:px-6 lg:grid-cols-[1.1fr_1fr]">
                <div class="rounded-3xl border border-emerald-100/80 bg-white/95 p-10 shadow-sm backdrop-blur">
                    <h2 class="text-3xl font-semibold text-emerald-900">Jinsi mfumo unafanya kazi</h2>
                    <p class="mt-3 text-sm text-emerald-800/80">Four streamlined steps carry your ideas from submission to committee deliberation.</p>
                    <div class="mt-8 grid gap-6 md:grid-cols-2">
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-base font-bold text-white">1</div>
                            <h3 class="mt-4 text-lg font-semibold text-emerald-900">Jisajili</h3>
                            <p class="text-sm text-emerald-800/80">Secure your account with National ID, email, or phone verification.</p>
                        </div>
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-base font-bold text-white">2</div>
                            <h3 class="mt-4 text-lg font-semibold text-emerald-900">Chagua mswada</h3>
                            <p class="text-sm text-emerald-800/80">Filter by topic, committee, county impact, or urgency.</p>
                        </div>
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-base font-bold text-white">3</div>
                            <h3 class="mt-4 text-lg font-semibold text-emerald-900">Eleza maoni yako</h3>
                            <p class="text-sm text-emerald-800/80">Submit clause-level feedback, sentiment, and attachments.</p>
                        </div>
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-base font-bold text-white">4</div>
                            <h3 class="mt-4 text-lg font-semibold text-emerald-900">Fuatilia safari</h3>
                            <p class="text-sm text-emerald-800/80">Track status updates, committee responses, and final outcomes.</p>
                        </div>
                    </div>
                </div>
                <div class="relative flex items-center justify-center">
                    <div class="absolute inset-0 rounded-[32px] bg-emerald-200/40 blur-3xl" aria-hidden="true"></div>
                    <img :src="dashboardIllustration" alt="Legislative analytics" class="relative w-full max-w-md rounded-[32px] border-4 border-white shadow-2xl" />
                </div>
            </section>

            <section id="personas" class="mx-auto w-full max-w-7xl space-y-8 px-4 py-16 md:px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-semibold text-emerald-900">Tailored journeys for every participant</h2>
                    <p class="mx-auto mt-3 max-w-3xl text-sm text-emerald-800/80">
                        Citizens, legislators, and clerks access role-specific dashboards, AI summaries, and workflow tools to keep the public participation cycle accountable.
                    </p>
                </div>
                <div class="grid gap-6 md:grid-cols-3">
                    <article class="rounded-3xl border border-emerald-100/80 bg-white/95 p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-emerald-900">Citizen portal</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">Explore open bills, submit feedback, and receive deadline reminders.</p>
                        <ul class="mt-4 space-y-2 text-sm text-emerald-800">
                            <li>• Clause templates with sentiment tagging</li>
                            <li>• Tracking IDs with real-time notifications</li>
                            <li>• County-level engagement snapshots</li>
                        </ul>
                    </article>
                    <article class="rounded-3xl border border-emerald-100/80 bg-white/95 p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-emerald-900">Legislator workspace</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">Surface the most debated clauses, sentiment analysis, and committee-ready reports.</p>
                        <ul class="mt-4 space-y-2 text-sm text-emerald-800">
                            <li>• House-specific dashboards & filters</li>
                            <li>• AI summaries plus citizen quotes</li>
                            <li>• Exportable briefs for committee sittings</li>
                        </ul>
                    </article>
                    <article class="rounded-3xl border border-emerald-100/80 bg-white/95 p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-emerald-900">Clerk command center</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">Manage publication schedules, moderation, and AI-assisted aggregation.</p>
                        <ul class="mt-4 space-y-2 text-sm text-emerald-800">
                            <li>• Participation window orchestration</li>
                            <li>• Quality control & comment moderation</li>
                            <li>• Analytics for compliance reporting</li>
                        </ul>
                    </article>
                </div>
            </section>

            <section id="channels" class="mx-auto w-full max-w-7xl rounded-3xl border border-emerald-100/80 bg-emerald-50/80 px-4 py-16 shadow-inner md:px-6">
                <h2 class="text-center text-3xl font-semibold text-emerald-900">Njia nyingi za kushiriki</h2>
                <p class="mx-auto mt-3 max-w-2xl text-center text-sm text-emerald-800/80">
                    Whether you are online or offline, Huduma Ya Raia keeps the public consultative process inclusive and accessible.
                </p>
                <div class="mt-10 grid gap-8 md:grid-cols-3">
                    <div class="rounded-2xl bg-white/95 p-6 text-center shadow-sm">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                            <Globe class="h-8 w-8 text-emerald-700" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-emerald-900">Web platform</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">Responsive experience with full bill context, comments, and dashboards.</p>
                    </div>
                    <div class="rounded-2xl bg-white/95 p-6 text-center shadow-sm">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                            <Smartphone class="h-8 w-8 text-emerald-700" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-emerald-900">SMS &amp; USSD</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">Dial *707# or text 40404 to receive summaries and submit short-form views.</p>
                    </div>
                    <div class="rounded-2xl bg-white/95 p-6 text-center shadow-sm">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                            <Phone class="h-8 w-8 text-emerald-700" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-emerald-900">Voice IVR</h3>
                        <p class="mt-2 text-sm text-emerald-800/80">Call 0800-HUDUMA to listen to audio summaries and record feedback.</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-7xl px-4 py-16 md:px-6">
                <div class="grid gap-8 rounded-3xl border border-emerald-100/80 bg-white/95 p-10 shadow-sm md:grid-cols-[1.2fr_1fr]">
                    <div>
                        <h2 class="text-3xl font-semibold text-emerald-900">Transparency metrics at a glance</h2>
                        <p class="mt-3 text-sm text-emerald-800/80">
                            Real-time dashboards give Parliament, civil society, and citizens shared visibility into public participation performance.
                        </p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-emerald-50/70 p-4 text-center shadow-inner">
                                <p class="text-3xl font-bold text-emerald-600">47</p>
                                <p class="text-sm text-emerald-800/80">Counties represented</p>
                            </div>
                            <div class="rounded-2xl bg-emerald-50/70 p-4 text-center shadow-inner">
                                <p class="text-3xl font-bold text-emerald-600">0</p>
                                <p class="text-sm text-emerald-800/80">Bills live today</p>
                            </div>
                            <div class="rounded-2xl bg-emerald-50/70 p-4 text-center shadow-inner">
                                <p class="text-3xl font-bold text-emerald-600">0</p>
                                <p class="text-sm text-emerald-800/80">Submissions received</p>
                            </div>
                            <div class="rounded-2xl bg-emerald-50/70 p-4 text-center shadow-inner">
                                <p class="text-3xl font-bold text-emerald-600">0</p>
                                <p class="text-sm text-emerald-800/80">Active participants</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-emerald-100/80 bg-gradient-to-br from-emerald-600 via-emerald-500 to-emerald-400 p-8 text-white shadow-lg">
                        <h3 class="text-2xl font-semibold">Join the civic dialogue</h3>
                        <p class="mt-3 text-sm text-white/85">
                            Huduma Ya Raia digitises public participation without replacing in-person forums. Use it to prepare, contribute, and follow each bill’s journey.
                        </p>
                        <Link
                            :href="register()"
                            class="mt-6 inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                        >
                            Create your civic account
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="mt-12 border-t border-emerald-100 bg-white/90 py-12">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 md:flex-row md:justify-between md:px-6">
                <div class="max-w-sm space-y-3">
                    <AppLogo />
                    <p class="text-sm text-emerald-800/80">
                        Empowering every Kenyan to participate in the legislative process with clarity, accountability, and accessible technology.
                    </p>
                </div>
                <div class="grid flex-1 gap-8 sm:grid-cols-3">
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-900">Quick links</h4>
                        <ul class="mt-3 space-y-2 text-sm text-emerald-700">
                            <li><a href="#features" class="transition hover:text-emerald-900">Active bills</a></li>
                            <li><a href="#how-it-works" class="transition hover:text-emerald-900">How to participate</a></li>
                            <li><Link :href="'/submissions/track'" class="transition hover:text-emerald-900">Track submission</Link></li>
                            <li><a href="#channels" class="transition hover:text-emerald-900">Support channels</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-900">Contact</h4>
                        <ul class="mt-3 space-y-2 text-sm text-emerald-700">
                            <li>SMS: 40404</li>
                            <li>USSD: *707#</li>
                            <li>Call: 0800-HUDUMA</li>
                            <li>Email: info@huduma.go.ke</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-900">Partners</h4>
                        <ul class="mt-3 space-y-2 text-sm text-emerald-700">
                            <li>Parliament of Kenya</li>
                            <li>Ministry of ICT</li>
                            <li>Digital Kenya Initiative</li>
                            <li>Civil society networks</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-xs text-emerald-700/70">
                &copy; {{ new Date().getFullYear() }} Republic of Kenya. All rights reserved.
            </div>
        </footer>
    </div>
</template>
