<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>
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
                    <a href="#about" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">About</a>
                    <a href="#pricing" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Pricing</a>
                    <a href="#contact" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Contact</a>
                    {{-- <a href="#blog" class="font-medium transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200 hover:text-teal-600' : 'text-white/90 hover:text-white'">Blog</a> --}}
                </div>
                <button type="button" class="md:hidden p-2 transition-colors" :class="scrolled ? 'text-slate-700 dark:text-slate-200' : 'text-white'" aria-label="Toggle menu">
                    <x-icon name="o-bars-3" class="w-6 h-6" />
                </button>
            </div>
        </nav>
    </header>

    {{-- Hero Section --}}
    <section id="home" class="relative min-h-[110vh] md:min-h-[90vh] flex items-center overflow-hidden">
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
                    <span class="inline-block bg-teal-600 -rotate-2 px-3 py-1 rounded-none text-white font-bold shadow-lg">Smart Solution</span> 
                    <span class="">for your Apartment Rental Business.</span>
                </h1>
                <p class="text-lg text-white/90 mb-8 max-w-xl mx-auto lg:mx-0">
                    Take control of your business with our smart solution. Manage your apartment rental business to the next level with ease.
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
                    <div class="absolute inset-0 bg-slate-800/80 rounded-2xl shadow-2xl transform rotate-5 scale-95"></div>
                    <div class="absolute inset-0 bg-slate-800/90 rounded-2xl shadow-2xl transform -rotate-5 scale-95"></div>
                    <div class="relative bg-slate-800 rounded-2xl overflow-hidden shadow-2xl p-3 border border-slate-700">
                        <div class="flex gap-2 mb-4">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        </div>
                        <img src="{{ asset('landing-page-images/dashboard.png') }}" alt="Dashboard" class="rounded-2xlw-full h-full object-cover" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature Packed --}}
    <section id="features" class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-800 dark:text-slate-200 mb-12">Features </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach([
                    ['title' => 'Modern Dashboard', 'icon' => 'o-squares-2x2', 'description' => 'A clean, intuitive dashboard that gives you full visibility of your rental business at a glance.'],
                    ['title' => 'Track Performance & Analytics', 'icon' => 'o-chart-bar', 'description' => 'Understand your revenue, occupancy, and trends with real-time rental analytics.'],
                    ['title' => 'Management Tenants', 'icon' => 'o-device-phone-mobile', 'description' => 'Organize tenant details, payments, and rental history all in one place'],
                    ['title' => "Smart Listing Solution ", 'icon' => 'o-puzzle-piece', 'description' => 'Create, update, and manage property listings faster with smart tools built for landlords'],
                    ['title' => 'Automations & Notifications', 'icon' => 'o-bell', 'description' => 'Automate reminders, alerts, and updates so nothing slips through the cracks'],
                    ['title' => 'Always Expanding', 'icon' => 'o-arrow-path', 'description' => 'Expand more features to support your growing rental business. Calendar Management, AI 24/7 AI Chat Support, Email/SMS notifications, etc. on the pipeline.'],
                ] as $feature)
                    <div class="bg-slate-50 dark:bg-base-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow border border-slate-100 dark:border-slate-700/50">
                        <div class="w-14 h-14 rounded-xl bg-teal-400/20 dark:bg-teal-900/40 flex items-center justify-center mb-4 mx-auto">
                            <x-icon name="{{ $feature['icon'] }}" class="w-7 h-7 text-teal-600 dark:text-teal-400" />
                        </div>
                        <h3 class="text-xl text-center font-bold text-slate-800 dark:text-slate-200 mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-slate-600 text-center dark:text-slate-400">{{ $feature['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- About --}}
    <section id="about" class="py-20 bg-slate-50 dark:bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="rounded-2xl overflow-hidden shadow-xl bg-slate-800">
                    <div class="w-full h-full p-3 flex flex-col gap-4">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        </div>
                        <img src="{{ asset('landing-page-images/tenant-turnover.png') }}" alt="Dashboard" class="rounded-2xlw-full h-full object-cover" />
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Dashboards & Analytics</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Get a clear, real-time overview of your entire rental business in one place.
                        Monitor properties, tenants, and revenue without switching between tools. 
                        Designed to be clean, fast, and easy to use—so you stay focused on what matters.
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
    <section class="py-20 bg-white dark:bg-base-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1">
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Track High Performing Rental Units & Overall Business Performance</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Turn your rental data into actionable insights with powerful analytics.
                        Track income, occupancy rates, and tenant trends in real time.
                        Make smarter decisions backed by clear reports and visual breakdowns.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                        Learn More
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
                <div class="order-1 lg:order-2 rounded-xl overflow-hidden shadow-xl bg-slate-100 dark:bg-slate-800 p-3 h-full">
                    <img src="{{ asset('landing-page-images/revenue.png') }}" alt="Dashboard" class="w-full h-full object-cover" />
                </div>
            </div>
        </div>
    </section>

    {{-- Simple Device Management --}}
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="rounded-2xl overflow-scroll shadow-xl bg-[#282828] p-4">
                    <div class="flex items-center justify-center">
                        <img src="{{ asset('landing-page-images/tenant-dashboard-1.png') }}" alt="Dashboard" class="rounded-2xl w-[205px] object-cover" />
                        <img src="{{ asset('landing-page-images/tenant-apartments.png') }}" alt="apartments" class="rounded-2xl w-[175px] mr-2 object-cover" />
                        <img src="{{ asset('landing-page-images/tenant-notification.png') }}" alt="notification" class="rounded-2xl w-[175px] object-cover" />
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">Tenants Account & Login for One-platform Access</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        Tenants get their own login to access rental information anytime, anywhere.
                        View dues, receive announcements, and stay updated without hassle.
                        One platform that keeps landlords and tenants perfectly connected.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                        Learn More
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Fair Pricing --}}
    <section id="pricing" class="pt-18 lg:pb-0 pb-18 bg-linear-to-b from-slate-100 via-slate-100 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-800 dark:text-slate-200 mb-12">
                Fair Pricing
                <p class="mt-2 text-[16px] font-normal text-center text-slate-600 dark:text-slate-400">(Coming Soon)</p>
            </h2>
            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="relative bg-white dark:bg-base-200 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-8 flex flex-col">
                    <div class="absolute top-0 left-0 flex items-center justify-center h-full w-full bg-black/60 rounded-2xl backdrop-blur">
                        <p class="text-white/60 text-2xl font-bold">Coming Soon</p>
                    </div>
                    <h3 class="text-3xl font-bold text-teal-600 mb-2">Starter</h3>
                    <p class="text-4xl font-bold text-teal-900 mb-6">$9.99<span class="text-lg font-normal text-slate-500">/mo</span></p>
                    <ul class="space-y-3 mb-8 flex-1">
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />2 rental units</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />Basic Usage & Integrations</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />Unlimited notifications</li>
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><x-icon name="o-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />Tech Support & Training</li>
                    </ul>
                    <a href="#" class="block text-center py-3 rounded-full font-semibold bg-teal-500 hover:bg-teal-600 text-white transition-colors duration-300">
                        Get Started
                    </a>
                </div>
                <div class="relative bg-teal-700 rounded-2xl shadow-xl p-8 flex flex-col">
                    <div class="absolute top-0 left-0 flex items-center justify-center h-full w-full bg-black/60 rounded-2xl backdrop-blur">
                        <p class="text-white/80 text-2xl font-bold">Coming Soon</p>
                    </div>
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-cyan-500 text-white text-md font-medium rounded-full">Popular</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Pro</h3>
                    <p class="text-4xl font-bold text-white mb-6">$12<span class="text-lg font-normal text-indigo-200">/mo</span></p>
                    <ul class="space-y-3 mb-8 flex-1">
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Unlimited Rental Units</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Unlimited Tenants</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Unlimited Notifications</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Advanced Integrations</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Priority Customer Support</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />AI Automation & Integration</li>
                        <li class="flex items-center gap-2 text-indigo-100"><x-icon name="o-check-circle" class="w-5 h-5 text-white shrink-0" />Calendar Management</li>
                    </ul>
                    <a href="#" class="block text-center py-3 rounded-full font-semibold bg-white text-teal-600 hover:bg-teal-100 transition-colors duration-200">
                        Sign Up Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Create your free account today! --}}
    <section id="contact" class="relative lg:pt-50 pt-18 pb-18 bg-slate-800 overflow-hidden">
        <svg class="absolute top-0 left-0 w-full text-white" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0 0L50 20C100 40 200 80 300 90C400 100 500 80 600 70C700 60 800 80 900 85C1000 90 1100 75 1150 67.5L1200 60V0H0Z" fill="currentColor"/>
        </svg>
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="text-2xl md:text-3xl font-bold text-white mb-4">Do You Own Apartment Rentals? Be Our Early Adopter</p>
            <p class="text-white/90 mb-6 text-sm lg:w-3/4 w-full mx-auto">
                Start using the platform before the official launch.
                Get early access to powerful features, priority updates, and dedicated support from our team.
                Manage your apartments smarter while helping shape the future of the platform.
            </p>
            <a href="mailto:marnelle24@gmail.com" target="_blank" class="inline-flex items-center px-8 py-3.5 rounded-lg font-semibold bg-teal-600 hover:bg-teal-500 text-white transition-colors">
                Join Early Access Program
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
