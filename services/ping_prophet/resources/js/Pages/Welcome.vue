<script setup>
// welcome.vue
import {Head, Link} from '@inertiajs/vue3';

defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  laravelVersion: {
    type: String,
    required: true,
  },
  phpVersion: {
    type: String,
    required: true,
  },
});

function handleImageError() {
  document.getElementById('screenshot-container')?.classList.add('!hidden');
  document.getElementById('docs-card')?.classList.add('!row-span-1');
  document.getElementById('docs-card-content')?.classList.add('!flex-row');
  document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
  <Head title="Welcome"/>
  <div class="flex min-h-full flex-col">
    <header>
      <nav>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-50 flex justify-between py-8">
          <div class="relative z-10 flex items-center gap-16">
            <a aria-label="Home" href="/">
              <img src="/img/logo.png" alt="Ping Prophet" class="w-24"/>
            </a>
            <div class="hidden lg:flex lg:gap-10">
              <a class="relative -mx-3 -my-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors delay-150 hover:text-gray-900 hover:delay-0"
                 href="#features">
                <span class="relative z-10">Features</span>
              </a>
              <a class="relative -mx-3 -my-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors delay-150 hover:text-gray-900 hover:delay-0"
                 href="#pricing">
                <span class="relative z-10">Pricing</span>
              </a>
              <a class="relative -mx-3 -my-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors delay-150 hover:text-gray-900 hover:delay-0"
                 href="#faqs">
                <span class="relative z-10">FAQs</span>
              </a>
            </div>
          </div>

          <div class="flex items-center gap-6">
            <div class="lg:hidden"><!-- Mobile menu button --></div>

            <template v-if="canLogin">
              <div v-if="$page.props.auth.user" class="hidden lg:block">
                <Link :href="route('dashboard')"
                      class="inline-flex justify-center rounded-lg border py-[calc(theme(spacing.2)-1px)] px-[calc(theme(spacing.3)-1px)] text-sm outline-2 outline-offset-2 transition-colors border-gray-300 text-gray-700 hover:border-gray-400 active:bg-gray-100 active:text-gray-700/80">
                  Dashboard
                </Link>
              </div>

              <template v-else>
                <Link :href="route('login')"
                      class="inline-flex justify-center rounded-lg border py-[calc(theme(spacing.2)-1px)] px-[calc(theme(spacing.3)-1px)] text-sm outline-2 outline-offset-2 transition-colors border-gray-300 text-gray-700 hover:border-gray-400 active:bg-gray-100 active:text-gray-700/80 hidden lg:block">
                  Log in
                </Link>

                <Link v-if="canRegister" :href="route('register')"
                      class="inline-flex justify-center rounded-lg py-2 px-3 text-sm font-semibold outline-2 outline-offset-2 transition-colors bg-gray-800 text-white hover:bg-gray-900 active:bg-gray-800 active:text-white/80 hidden lg:block">
                  Get Started
                </Link>
              </template>
            </template>
          </div>
        </div>
      </nav>
    </header>

    <main class="flex-auto">
      <div class="overflow-hidden py-20 sm:py-32 lg:pb-32 xl:pb-36">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="lg:grid lg:grid-cols-12 lg:gap-x-8 lg:gap-y-20">
            <div class="relative z-10 mx-auto max-w-2xl lg:col-span-7 lg:max-w-none lg:pt-6 xl:col-span-6">
              <h1 class="text-4xl font-medium tracking-tight text-gray-900">Supercharge your API integrations.</h1>
              <p class="mt-6 text-lg text-gray-600">Ping Prophet provides reliable and fast APIs for MNP, HLR and domain
                purchases. Get started in minutes.</p>
              <div class="mt-8 flex flex-wrap gap-x-6 gap-y-4">
                <a class="inline-flex justify-center rounded-lg py-2 px-3 text-sm font-semibold outline-2 outline-offset-2 transition-colors bg-gray-800 text-white hover:bg-gray-900 active:bg-gray-800 active:text-white/80 hidden lg:block"
                   href="/register">Get Started</a>
                <a class="inline-flex justify-center rounded-lg border py-[calc(theme(spacing.2)-1px)] px-[calc(theme(spacing.3)-1px)] text-sm outline-2 outline-offset-2 transition-colors border-gray-300 text-gray-700 hover:border-gray-400 active:bg-gray-100 active:text-gray-700/80"
                   variant="outline" color="gray" href="#">View Documentation</a>
              </div>
            </div>
            <div class="relative mt-10 sm:mt-20 lg:col-span-5 lg:row-span-2 lg:mt-0 xl:col-span-6">
              <img src="/img/header.png" alt="Screenshot" class="w-full h-full object-cover"/>
            </div>
          </div>
        </div>
      </div>

      <section id="features" class="py-20 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="mx-auto max-w-2xl sm:text-center">
            <h2 class="text-3xl font-medium tracking-tight text-gray-900">Everything you need for seamless API
              integrations.</h2>
            <p class="mt-2 text-lg text-gray-600">Ping Prophet offers a comprehensive suite of APIs to power your
              applications.</p>
          </div>
          <ul role="list"
              class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-6 text-sm sm:mt-20 sm:grid-cols-2 md:gap-y-10 lg:max-w-none lg:grid-cols-3">
            <li class="rounded-2xl border border-gray-200 p-8">
              <h3 class="text-lg font-semibold text-gray-900">MNP API</h3>
              <p class="mt-2 text-gray-700">Seamlessly port mobile numbers with our reliable MNP API.</p>
            </li>
            <li class="rounded-2xl border border-gray-200 p-8">
              <h3 class="text-lg font-semibold text-gray-900">HLR API</h3>
              <p class="mt-2 text-gray-700">Verify the status and details of mobile numbers with our robust HLR API.</p>
            </li>
            <li class="rounded-2xl border border-gray-200 p-8">
              <h3 class="text-lg font-semibold text-gray-900">Domain API</h3>
              <p class="mt-2 text-gray-700">Easily purchase and manage domain names using our intuitive Domain API.</p>
            </li>
            <!-- Add more feature items as needed -->
          </ul>
        </div>
      </section>

      <section id="pricing" class="border-t border-gray-200 py-20 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
              <div class="mx-auto max-w-2xl sm:text-center"><h2
                  class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Our Services and Pricing</h2>
                <p class="mt-6 text-lg leading-8 text-gray-600">We offer a range of services to meet your needs. Choose
                  the service that best fits your requirements.</p></div>
              <div class="mt-20">
                <ul role="list" class="grid gap-16 sm:grid-cols-2 lg:grid-cols-3">
                  <li><h3 class="text-2xl font-bold">MNP</h3>
                    <p class="mt-4">0.001 Euro per query</p></li>
                  <li><h3 class="text-2xl font-bold">HLR</h3>
                    <p class="mt-4">0.002 Euro per query</p></li>
                  <li><h3 class="text-2xl font-bold">Domain</h3>
                    <p class="mt-4">Pricing depends on TLD's</p></li>
                </ul>
              </div>
            </div>
          </div>

        </div>
      </section>

      <section id="faqs" aria-labelledby="faqs-title" class="border-t border-gray-200 py-20 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="mx-auto max-w-2xl lg:mx-0">
            <h2 id="faqs-title" class="text-3xl font-medium tracking-tight text-gray-900">Frequently asked
              questions</h2>
            <p class="mt-2 text-lg text-gray-600">If you have anything else you want to ask, <a
                href="mailto:info@example.com" class="text-gray-900 underline">reach out to us</a>.</p>
          </div>
          <!-- Add FAQs related to Ping Prophet here -->
        </div>
      </section>
    </main>

    <footer class="border-t border-gray-200">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-start justify-between gap-y-12 pb-6 pt-16 lg:flex-row lg:items-center lg:py-16">
          <div>
            <div class="flex items-center text-gray-900">
              <img src="/img/logo.png" alt="Ping Prophet" class="w-24"/>
              <div class="ml-4">
                <p class="text-base font-semibold">Ping Prophet</p>
                <p class="mt-1 text-sm">MNP, HLR and Domain APIs done right.</p>
              </div>
            </div>
            <nav class="mt-11 flex gap-8">
              <a class="relative -mx-3 -my-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors delay-150 hover:text-gray-900 hover:delay-0"
                 href="#features">
                <span class="relative z-10">Features</span>
              </a>
              <a class="relative -mx-3 -my-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors delay-150 hover:text-gray-900 hover:delay-0"
                 href="#pricing">
                <span class="relative z-10">Pricing</span>
              </a>
              <a class="relative -mx-3 -my-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors delay-150 hover:text-gray-900 hover:delay-0"
                 href="#faqs">
                <span class="relative z-10">FAQs</span>
              </a>
            </nav>
          </div>
          <!-- Add newsletter sign-up form here -->
        </div>
      </div>
    </footer>
  </div>
</template>
