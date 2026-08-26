@verbatim
<!-- Zero State: No Locales -->
<div v-if="!loading && locales.length === 0" class="flex-1 flex flex-col items-center justify-center p-10 text-center">
    <div class="bg-gray-100 p-6 rounded-full mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h3 class="text-xl font-medium text-gray-900 mb-2">Welcome to Glosa</h3>
    <p class="text-gray-500 max-w-md mb-8">It looks like you haven't set up any languages yet. Please add a language (Locale) to start managing your translations.</p>
    <div class="flex gap-4">
        <button @click="showAddLocaleModal=true" type="button" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors">
            + Add First Language
        </button>
    </div>
</div>
@endverbatim
