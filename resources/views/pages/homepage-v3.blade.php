{{-- Smart Home Dashboard Landing Page (homepage-v3) --}}
<div class="min-h-screen bg-white dark:bg-base-200 text-slate-900 dark:text-slate-100">
    {{-- Header / Navigation --}}
    <header class="fixed top-0 left-0 right-0 z-50 bg-transparent">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="#" class="flex items-center gap-1">
                    <span class="text-xl md:text-2xl font-bold text-blue-800 dark:text-blue-600">Smart</span>
                    <span class="text-xl md:text-2xl font-bold text-blue-500 dark:text-blue-400">Home</span>
                </a>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#home" class="text-white/90 hover:text-white font-medium transition-colors">Home</a>
                    <a href="#features" class="text-white/90 hover:text-white font-medium transition-colors">Features</a>
                    <a href="#pricing" class="text-white/90 hover:text-white font-medium transition-colors">Pricing</a>
                    <a href="#about" class="text-white/90 hover:text-white font-medium transition-colors">About</a>
                </div>
            </div>
        </nav>
    </header>

    {{-- Hero Section --}}
    <section id="home" class="relative min-h-[90vh] flex items-center overflow-hidden">
        <div class="absolute inset-0 bg-linear-to-r from-blue-600 via-blue-500 to-emerald-500">
            <div class="absolute inset-0 opacity-30">
                <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl"></div>
                <svg class="absolute bottom-0 left-0 w-full h-24 text-white/20" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M0 120L50 100C100 80 200 40 300 30C400 20 500 40 600 50C700 60 800 40 900 35C1000 30 1100 45 1150 52.5L1200 60V120H0Z" fill="currentColor"/>
                </svg>
            </div>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 flex flex-col lg:flex-row items-center gap-12">
            <div class="flex-1 text-center lg:text-left">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                    The Ultimate Smart Home Dashboard.
                </h1>
                <p class="text-lg text-white/90 mb-8 max-w-xl mx-auto lg:mx-0">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.
                </p>
                <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                    <a href="#" class="inline-flex items-center px-8 py-3 rounded-lg font-semibold bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg transition-colors">
                        Try It Free
                    </a>
                    <a href="#" class="inline-flex items-center px-8 py-3 rounded-lg font-semibold bg-white/90 hover:bg-white text-blue-600 border border-white/50 transition-colors">
                        Watch Video
                    </a>
                </div>
            </div>
            <div class="flex-1 relative hidden lg:block">
                {{-- Dashboard mockup stack --}}
                <div class="relative w-full max-w-lg mx-auto">
                    <div class="absolute inset-0 bg-slate-800/80 rounded-xl shadow-2xl transform rotate-3 scale-95"></div>
                    <div class="absolute inset-0 bg-slate-800/90 rounded-xl shadow-2xl transform -rotate-2 scale-95"></div>
                    <div class="relative bg-slate-800 rounded-xl shadow-2xl p-6 border border-slate-700">
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

    {{-- Trusted By / Logos --}}
    <section class="py-12 bg-white dark:bg-base-100 border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-slate-500 dark:text-slate-400 text-sm mb-8">Trusted by industry leaders</p>
            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12 opacity-70">
                <span class="text-xl font-bold text-slate-400 dark:text-slate-500">TC</span>
                <span class="text-xl font-bold text-slate-400 dark:text-slate-500">Forbes</span>
                <span class="text-xl font-bold text-slate-400 dark:text-slate-500">Bloomberg</span>
                <span class="text-xl font-bold text-slate-400 dark:text-slate-500">THE VERGE</span>
                <span class="text-xl font-bold text-slate-400 dark:text-slate-500">TNW</span>
            </div>
        </div>
    </section>

    {{-- Feature Packed --}}
    <section id="features" class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-800 dark:text-slate-200 mb-12">Feature Packed</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach([
                    ['title' => 'Custom Dashboard', 'desc' => 'Build the perfect view of your smart home with drag-and-drop widgets and real-time data.', 'icon' => 'o-squares-2x2'],
                    ['title' => 'Fully Customizable', 'desc' => 'Tailor every aspect to your preferences. Themes, layouts, and automation rules.', 'icon' => 'o-cog-6-tooth'],
                    ['title' => 'iOS & Android Apps', 'desc' => 'Control your home from anywhere with our native mobile applications.', 'icon' => 'o-device-phone-mobile'],
                    ['title' => "100's Of Integrations", 'desc' => 'Connect with popular smart devices and services through our growing ecosystem.', 'icon' => 'o-puzzle-piece'],
                    ['title' => 'Simple Mail Features', 'desc' => 'Get alerts and reports delivered to your inbox. Notifications that matter.', 'icon' => 'o-envelope'],
                    ['title' => 'Always Expanding', 'desc' => 'We ship new features and integrations regularly. Your dashboard gets better over time.', 'icon' => 'o-arrow-path'],
                ] as $feature)
                    <div class="bg-slate-50 dark:bg-base-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center mb-4">
                            <x-icon name="{{ $feature['icon'] }}" class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200 mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-slate-600 dark:text-slate-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dashboards & Analytics --}}
    <section class="py-20 bg-slate-50 dark:bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="rounded-xl overflow-hidden shadow-xl bg-slate-800 aspect-video">
                    <div class="w-full h-full p-6 flex flex-col gap-4">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        </div>
                        <div class="flex-1 grid grid-cols-2 gap-3">
                            <div class="bg-slate-700 rounded"></div>
                            <div class="bg-slate-700 rounded"></div>
                            <div class="col-span-2 bg-slate-700 rounded h-24"></div>
                            <div class="col-span-2 bg-slate-700 rounded h-32"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Dashboards & Analytics</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        See how your data is performing, customize your dashboard, and gain valuable insights into your home's activity. Make informed decisions with clear visualizations.
                    </p>
                    <a href="#" class="inline-flex items-center px-6 py-3 rounded-lg font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Custom Layouts --}}
    <section class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1">
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Custom Layouts</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Design your perfect smart home interface with drag-and-drop elements and flexible templates to fit your unique needs. No coding required.
                    </p>
                    <a href="#" class="inline-flex items-center px-6 py-3 rounded-lg font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                        Learn More
                    </a>
                </div>
                <div class="order-1 lg:order-2 relative rounded-xl overflow-hidden shadow-xl bg-slate-100 dark:bg-slate-800 aspect-video p-6">
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
    <section class="py-20 bg-slate-50 dark:bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="rounded-xl overflow-hidden shadow-xl bg-slate-800 aspect-video p-6">
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
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Simple Device Management</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Effortlessly control all your smart devices from one central hub, monitor their status, and automate routines with ease. One app to rule them all.
                    </p>
                    <a href="#" class="inline-flex items-center px-6 py-3 rounded-lg font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonial --}}
    <section class="relative py-24 bg-blue-600 overflow-hidden">
        <svg class="absolute top-0 left-0 w-full h-16 text-blue-600" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0 0L50 20C100 40 200 80 300 90C400 100 500 80 600 70C700 60 800 80 900 85C1000 90 1100 75 1150 67.5L1200 60V0H0Z" fill="currentColor" class="text-white dark:text-slate-900"/>
        </svg>
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
            <p class="text-2xl md:text-3xl font-bold text-white mb-4">Simply Amazing</p>
            <blockquote class="text-lg md:text-xl text-white/95 mb-8">
                "This app has transformed the way I interact with my smart home. Highly recommended!"
            </blockquote>
            <div class="flex items-center justify-center gap-4">
                <div class="w-14 h-14 rounded-full bg-white/30 flex items-center justify-center">
                    <x-icon name="o-user" class="w-8 h-8 text-white" />
                </div>
                <div class="text-left">
                    <p class="font-semibold text-white">Jessica Smith</p>
                    <p class="text-white/80 text-sm">Smart Home Enthusiast</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Fair Pricing --}}
    <section id="pricing" class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-800 dark:text-slate-200 mb-12">Fair Pricing</h2>
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                {{-- Starter --}}
                <div class="bg-white dark:bg-base-200 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-8 flex flex-col">
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-200 mb-2">Starter</h3>
                    <p class="text-4xl font-bold text-slate-800 dark:text-slate-200 mb-6">$0<span class="text-lg font-normal text-slate-500">/mo</span></p>
                    <ul class="space-y-3 mb-8 flex-1">
                        @foreach(['Basic Features', 'Limited Devices', 'Standard Support', 'Data Export', 'Free Trial'] as $item)
                            <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                <x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="#" class="block text-center py-3 rounded-lg font-semibold border-2 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Get Started
                    </a>
                </div>
                {{-- Pro --}}
                <div class="bg-blue-600 rounded-2xl shadow-xl p-8 flex flex-col relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-emerald-500 text-white text-sm font-medium rounded-full">Recommended</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Pro</h3>
                    <p class="text-4xl font-bold text-white mb-6">$12<span class="text-lg font-normal text-blue-200">/mo</span></p>
                    <ul class="space-y-3 mb-8 flex-1">
                        @foreach(['All Starter Features', 'Unlimited Devices', 'Premium Support', 'Advanced Analytics', 'Custom Integrations'] as $item)
                            <li class="flex items-center gap-2 text-blue-100">
                                <x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="#" class="block text-center py-3 rounded-lg font-semibold bg-white text-blue-600 hover:bg-blue-50 transition-colors">
                        Sign Up Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Bottom CTA --}}
    <section class="py-16 bg-slate-800 dark:bg-slate-900">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="text-2xl font-semibold text-white mb-6">Create your free account today!</p>
            <a href="#" class="inline-flex items-center px-8 py-3 rounded-lg font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                Sign Up Now
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 bg-slate-900 dark:bg-slate-950 text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm">© {{ date('Y') }} Smart Home. All rights reserved.</p>
                <div class="flex items-center gap-6 text-sm">
                    <a href="#" class="hover:text-white transition-colors">Terms</a>
                    <a href="#" class="hover:text-white transition-colors">Privacy</a>
                    <a href="#" class="hover:text-white transition-colors">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</div>
