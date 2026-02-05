{{-- Kadence SaaS-style Landing Page (homepage-v4) - Replicates https://demos.kadencewp.com/blocks-saas/ --}}
<style>html { scroll-behavior: smooth; }</style>
<div class="min-h-screen bg-white dark:bg-base-200 text-slate-900 dark:text-slate-100">
    {{-- Header / Navigation --}}
    <header class="fixed top-0 left-0 right-0 z-50 transition-colors duration-200" x-data="{ scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 50)" :class="scrolled ? 'bg-white/80 dark:bg-base-300/95 backdrop-blur shadow-sm' : 'bg-transparent'">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between transition-all duration-200" :class="scrolled ? 'h-20' : 'h-28'">
                <div class="transition-all duration-200" :class="scrolled ? 'text-4xl text-teal-500 dark:text-teal-400 scale-80' : 'text-5xl text-white scale-100'">
                    <x-app-brand icon-width="w-14 h-14" text-size="text-inherit" tagline-size="text-sm" one-color-logo="text-inherit" />
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#home" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Home</a>
                    <a href="#features" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Features</a>
                    <a href="#pricing" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Pricing</a>
                    <a href="#about" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">About</a>
                    <a href="#contact" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Contact</a>
                    <a href="#blog" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Blog</a>
                </div>
                <button type="button" class="md:hidden p-2 transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200' : 'text-white'" aria-label="Toggle menu">
                    <x-icon name="o-bars-3" class="w-6 h-6" />
                </button>
            </div>
        </nav>
    </header>

    {{-- Hero Section --}}
    <section id="home" class="relative min-h-[90vh] flex items-center overflow-hidden">
        <div class="absolute inset-0 bg-linear-to-br from-teal-600 via-teal-400 to-cyan-500">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-cyan-400/20 rounded-full blur-3xl"></div>
            </div>
            <svg class="absolute bottom-0 left-0 w-full h-24 text-white" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0 120L50 100C100 80 200 40 300 30C400 20 500 40 600 50C700 60 800 40 900 35C1000 30 1100 45 1150 52.5L1200 60V120H0Z" fill="currentColor"/>
            </svg>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 flex flex-col lg:flex-row items-center gap-12">
            <div class="flex-1 text-center lg:text-left">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                    Smart Solution for your Apartment Rental Business.
                </h1>
                <p class="text-lg text-white/90 mb-8 max-w-xl mx-auto lg:mx-0">
                    Take control of your business with our smart solution. Manage your apartment rental business to the next filter-list-level with ease.
                </p>
                <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                    <a href="#" class="inline-flex items-center px-8 py-3.5 rounded-lg font-semibold bg-white text-teal-600 hover:bg-blue-50 shadow-lg transition-colors">
                        Explore More
                    </a>
                    <a href="#" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-lg font-semibold bg-white/30 hover:bg-white/20 text-white border border-white/80 transition-colors">
                        <x-icon name="o-play-circle" class="w-5 h-5" />
                        Watch video
                    </a>
                </div>
            </div>
            <div class="flex-1 relative hidden lg:block">
                <div class="relative w-full max-w-lg mx-auto">
                    <div class="absolute inset-0 bg-slate-800/80 rounded-2xl shadow-2xl transform rotate-3 scale-95"></div>
                    <div class="absolute inset-0 bg-slate-800/90 rounded-2xl shadow-2xl transform -rotate-2 scale-95"></div>
                    <div class="relative bg-slate-800 rounded-2xl shadow-2xl p-6 border border-slate-700">
                        <div class="flex gap-2 mb-4">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="h-20 bg-slate-700 rounded-lg"></div>
                            <div class="h-20 bg-slate-700 rounded-lg"></div>
                            <div class="h-24 bg-slate-700 rounded-lg col-span-2"></div>
                        </div>
                        <div class="h-32 bg-slate-700 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature Packed --}}
    <section id="features" class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-800 dark:text-slate-200 mb-12">Feature Packed</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach([
                    ['title' => 'Modern Dashboard', 'icon' => 'o-squares-2x2'],
                    ['title' => 'Track Performance & Analytics', 'icon' => 'o-chart-bar'],
                    ['title' => 'Management Tenants', 'icon' => 'o-device-phone-mobile'],
                    ['title' => "Smart Listing Solution ", 'icon' => 'o-puzzle-piece'],
                    ['title' => 'Automations & Notifications', 'icon' => 'o-bell'],
                    ['title' => 'Always Expanding', 'icon' => 'o-arrow-path'],
                ] as $feature)
                    <div class="bg-slate-50 dark:bg-base-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow border border-slate-100 dark:border-slate-700/50">
                        <div class="w-14 h-14 rounded-xl bg-teal-400/20 dark:bg-teal-900/40 flex items-center justify-center mb-4 mx-auto">
                            <x-icon name="{{ $feature['icon'] }}" class="w-7 h-7 text-teal-600 dark:text-teal-400" />
                        </div>
                        <h3 class="text-xl text-center font-bold text-slate-800 dark:text-slate-200 mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-slate-600 text-center dark:text-slate-400">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean diam dolor, accumsan sed rutrum vel, dapibus et leo.</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dashboards & Analytics --}}
    <section class="py-20 bg-slate-50 dark:bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="rounded-2xl overflow-hidden shadow-xl bg-slate-800 aspect-video">
                    <div class="w-full h-full p-6 flex flex-col gap-4">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        </div>
                        <div class="flex-1 grid grid-cols-2 gap-3">
                            <div class="bg-slate-700 rounded-lg"></div>
                            <div class="bg-slate-700 rounded-lg"></div>
                            <div class="col-span-2 bg-slate-700 rounded-lg h-24"></div>
                            <div class="col-span-2 bg-slate-700 rounded-lg h-32"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Dashboards & Analytics</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec massa leo, rhoncus imperdiet vehicula sit amet, porta sed risus. Pellentesque dignissim finibus imperdiet.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                        Learn More
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Custom Layouts --}}
    <section id="about" class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1">
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Track High Performing Rental Units & Overall Business Performance</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec massa leo, rhoncus imperdiet vehicula sit amet, porta sed risus. Pellentesque dignissim finibus imperdiet.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                        Learn More
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
                <div class="order-1 lg:order-2 rounded-2xl overflow-hidden shadow-xl bg-slate-100 dark:bg-slate-800 aspect-video p-6">
                    <div class="grid grid-cols-2 gap-3 h-full">
                        <div class="bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                        <div class="bg-slate-200 dark:bg-slate-700 rounded-lg relative">
                            <span class="absolute top-2 right-2 text-emerald-500"><x-icon name="o-check-circle" class="w-6 h-6" /></span>
                        </div>
                        <div class="col-span-2 bg-slate-200 dark:bg-slate-700 rounded-lg flex-1"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Simple Device Management --}}
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="rounded-2xl overflow-hidden shadow-xl bg-slate-800 aspect-video p-6">
                    <div class="flex gap-2 mb-4">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    </div>
                    <div class="space-y-3">
                        @foreach([1,2,3,4,5] as $i)
                            <div class="flex items-center justify-between bg-slate-700 rounded-lg px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-600"></div>
                                    <div>
                                        <div class="h-3 w-24 bg-slate-600 rounded"></div>
                                        <div class="h-2 w-16 bg-slate-600 rounded mt-1"></div>
                                    </div>
                                </div>
                                <div class="w-12 h-6 bg-emerald-500/30 rounded-full"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Tenants Account & Login for One-platform Access</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec massa leo, rhoncus imperdiet vehicula sit amet, porta sed risus. Pellentesque dignissim finibus imperdiet.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                        Learn More
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials carousel --}}
    <section class="relative py-24 bg-teal-600 overflow-hidden" x-data="{
        current: 0,
        total: 4,
        next() { this.current = (this.current + 1) % this.total; this.resetTimer(); },
        prev() { this.current = (this.current - 1 + this.total) % this.total; this.resetTimer(); },
        goTo(i) { this.current = i; this.resetTimer(); },
        timer: null,
        startTimer() { this.timer = setInterval(() => this.next(), 6000); },
        resetTimer() { clearInterval(this.timer); this.startTimer(); },
        init() { this.startTimer(); }
    }" x-init="init()">
        <svg class="absolute top-0 left-0 w-full h-16 text-white" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0 0L50 20C100 40 200 80 300 90C400 100 500 80 600 70C700 60 800 80 900 85C1000 90 1100 75 1150 67.5L1200 60V0H0Z" fill="currentColor"/>
        </svg>
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
            {{-- Prev / Next --}}
            {{-- <button type="button" @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors" aria-label="Previous testimonial">
                <x-icon name="o-chevron-left" class="w-6 h-6" />
            </button>
            <button type="button" @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors" aria-label="Next testimonial">
                <x-icon name="o-chevron-right" class="w-6 h-6" />
            </button> --}}

            <div class="min-h-[280px] relative">
                @foreach([
                    ['title' => 'Simply Amazing', 'quote' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec massa leo, rhoncus imperdiet vehicula sit amet, porta sed risus. Pellentesque dignissim finibus imperdiet.', 'name' => 'Jimmy Smith', 'role' => 'Happy Customer'],
                    ['title' => 'Best Decision Ever', 'quote' => 'This product has completely transformed how we work. The team is more productive and our clients are happier. Could not recommend it more.', 'name' => 'Sarah Chen', 'role' => 'Product Manager'],
                    ['title' => 'Exactly What We Needed', 'quote' => 'We evaluated dozens of solutions and this was the only one that checked every box. Support is responsive and the platform keeps improving.', 'name' => 'Marcus Johnson', 'role' => 'CTO'],
                    ['title' => 'Game Changer', 'quote' => 'Within the first week we saw measurable results. The ROI was clear and the onboarding was smooth. Five stars from our entire team.', 'name' => 'Elena Rodriguez', 'role' => 'Operations Lead'],
                ] as $i => $t)
                    <div x-show="current === {{ $i }}"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-4"
                         class="absolute inset-0 flex flex-col items-center justify-center px-8">
                        <p class="text-2xl md:text-3xl font-bold text-white mb-4">{{ $t['title'] }}</p>
                        <blockquote class="text-lg md:text-xl text-white/95 mb-8 max-w-2xl">
                            {{ $t['quote'] }}
                        </blockquote>
                        <div class="flex items-center justify-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-white/30 flex items-center justify-center">
                                <x-icon name="o-user" class="w-8 h-8 text-white" />
                            </div>
                            <div class="text-left">
                                <p class="font-semibold text-white">{{ $t['name'] }}</p>
                                <p class="text-white/80 text-sm">{{ $t['role'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Dots --}}
            <div class="flex items-center justify-center gap-2 mt-6">
                @foreach([0,1,2,3] as $i)
                    <button type="button" @click="goTo({{ $i }})" class="w-2.5 h-2.5 rounded-full transition-colors" :class="current === {{ $i }} ? 'bg-white scale-125' : 'bg-white/50 hover:bg-white/70'" aria-label="Go to testimonial {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Fair Pricing --}}
    {{-- <section id="pricing" class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-800 dark:text-slate-200 mb-12">Fair Pricing</h2>
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-white dark:bg-base-200 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-8 flex flex-col">
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-200 mb-2">Starter</h3>
                    <p class="text-4xl font-bold text-slate-800 dark:text-slate-200 mb-6">$0<span class="text-lg font-normal text-slate-500">/mo</span></p>
                    <ul class="space-y-3 mb-8 flex-1">
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />5 Devices</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />1 month cloud retention</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />Unlimited notifications</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />Basic integrations</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />Always free</li>
                    </ul>
                    <a href="#" class="block text-center py-3 rounded-lg font-semibold border-2 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Get Started
                    </a>
                </div>
                <div class="bg-indigo-600 rounded-2xl shadow-xl p-8 flex flex-col relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-cyan-500 text-white text-sm font-medium rounded-full">Popular</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Pro</h3>
                    <p class="text-4xl font-bold text-white mb-6">$12<span class="text-lg font-normal text-indigo-200">/mo</span></p>
                    <ul class="space-y-3 mb-8 flex-1">
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Unlimited Devices</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />1 year cloud retention</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Unlimited notifications</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Advanced integrations</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Priority Customer Support</li>
                    </ul>
                    <a href="#" class="block text-center py-3 rounded-lg font-semibold bg-white text-indigo-600 hover:bg-indigo-50 transition-colors">
                        Sign Up Now
                    </a>
                </div>
            </div>
        </div>
    </section> --}}

    {{-- Create your free account today! --}}
    <section id="contact" class="py-16 bg-slate-800 dark:bg-slate-900">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="text-2xl font-semibold text-white mb-6">Create your free account today!</p>
            <a href="#" class="inline-flex items-center px-8 py-3.5 rounded-lg font-semibold bg-teal-600 hover:bg-teal-500 text-white transition-colors">
                Try for free
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 bg-slate-900 dark:bg-slate-950 text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs">© {{ date('Y') }} Rentory - Manage Your Apartment Rental Business with Ease</p>
                <div class="flex items-center gap-6 text-sm">
                    <a href="#home" class="hover:text-white transition-colors">Home</a>
                    <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
                    <a href="#about" class="hover:text-white transition-colors">About</a>
                    <a href="#contact" class="hover:text-white transition-colors">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</div>
