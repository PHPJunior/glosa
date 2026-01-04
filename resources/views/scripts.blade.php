<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate3d(0, 10px, 0);
        }

        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out forwards;
    }
</style>
<script type="module">
    import { createApp, ref, computed, onMounted } from 'vue';

    // Debug logging
    console.log('Glosa: Initializing Vue app...');

    try {
        // Configuration
        const config = {
            prefix: "{{ config('glosa.route_prefix', 'glosa') }}",
            csrf: "{{ csrf_token() }}"
        };

        // Shared API utilities
        const api = {
            async handleResponse(response) {
                if (!response.ok) {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const err = await response.json();
                        throw new Error(err.message || `HTTP error! status: ${response.status}`);
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            },
            async get(url) {
                const response = await fetch(`/${config.prefix}${url}`, {
                    headers: { 'Accept': 'application/json' }
                });
                return this.handleResponse(response);
            },
            async post(url, body, isMultipart = false) {
                const headers = {
                    'X-CSRF-TOKEN': config.csrf,
                    'Accept': 'application/json'
                };
                if (!isMultipart) {
                    headers['Content-Type'] = 'application/json';
                }

                const res = await fetch(`/${config.prefix}${url}`, {
                    method: 'POST',
                    headers: headers,
                    body: isMultipart ? body : JSON.stringify(body)
                });
                return this.handleResponse(res);
            },
            async put(url, data) {
                const response = await fetch(`/${config.prefix}${url}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                return this.handleResponse(response);
            },
            async delete(url) {
                const response = await fetch(`/${config.prefix}${url}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'Accept': 'application/json'
                    }
                });
                return this.handleResponse(response);
            }
        };

        const TranslationsView = {
            template: `
                    <div class="flex flex-col h-full"> 
                    <!-- Toolbar -->
                    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white sticky top-0 z-30" v-if="locales.length > 0">
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input v-model="search" @input="debouncedSearch" type="text" placeholder="Search keys..." class="block w-full rounded-md border border-gray-300 py-2 pl-10 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            
                            <!-- Filters -->
                            <select v-model="filters.group" @change="fetchKeys" class="block w-full sm:w-40 rounded-md border border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Groups</option>
                                <option v-for="group in groups" :key="group" :value="group">@{{ group }}</option>
                            </select>

                            <select v-model="filters.missing_locale" @change="fetchKeys" class="block w-full sm:w-48 rounded-md border border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Translations</option>
                                <option v-for="locale in locales" :key="locale.id" :value="locale.code">Missing in @{{ locale.name }}</option>
                            </select>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-3">
                             <button @click="showAddKeyModal=true" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Add Key
                            </button>
                            <button @click="showAddLocaleModal=true" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Languages
                            </button>
                            <button @click="showExportModal=true" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Export
                            </button>
                            <button @click="showImportModal=true" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Import
                            </button>
                        </div>
                    </div>

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
                            <button @click="showAddLocaleModal=true" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                + Add First Language
                            </button>
                        </div>
                    </div>

                    <!-- Data Grid header (sticky) -->
                    <div v-else class="flex-1 overflow-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-gray-200 border-separate border-spacing-0">
                                <thead class="bg-gray-50  z-10">
                                    <tr>
                                        <th scope="col" class="sticky left-0 z-20 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            Key
                                        </th>
                                        <th v-for="locale in locales" :key="locale.id" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 min-w-[300px] group">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span>@{{ locale.code }}</span>
                                                    <span v-if="locale.is_default" class="bg-indigo-100 text-indigo-700 text-[10px] px-1.5 py-0.5 rounded border border-indigo-200">Default</span>
                                                </div>
                                                 <!-- Locale Actions -->
                                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button @click="openEditLocaleModal(locale)" class="p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-gray-100" title="Edit Locale">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                    </button>
                                                    <button @click="deleteLocale(locale.id)" class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-gray-100" title="Delete Locale">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="loading" class="animate-pulse">
                                        <td :colspan="locales.length + 1" class="px-6 py-4 text-center text-sm text-gray-500">Loading data...</td>
                                    </tr>
                                    <tr v-else-if="keys.length === 0">
                                        <td :colspan="locales.length + 1" class="px-6 py-10 text-center text-gray-500">
                                            No translation keys found. <br>
                                            <button @click="showAddKeyModal=true" class="text-indigo-600 hover:underline mt-2">Create one</button>.
                                        </td>
                                    </tr>
                                    <tr v-for="keyData in keys" :key="keyData.id" class="hover:bg-gray-50 group transition-colors">
                                        <td class="sticky left-0 z-10 bg-white group-hover:bg-gray-50 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                            <div class="flex flex-col">
                                                <span class="text-indigo-600 font-mono">@{{ keyData.key_name }}</span>
                                                <span class="text-xs text-gray-400">@{{ keyData.group }}</span>
                                            </div>
                                            <!-- Row Actions -->
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 flex gap-2 transition-opacity bg-white pl-2">
                                                <button @click="openEditModal(keyData)" class="text-gray-400 hover:text-indigo-600" title="Edit Key">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteKey(keyData.id)" class="text-gray-400 hover:text-red-600" title="Delete Key">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td v-for="locale in locales" :key="locale.id" class="px-6 py-3 whitespace-normal text-sm text-gray-500 border-r border-gray-50 relative group/cell">
                                             <div class="relative w-full h-full min-h-[40px] flex items-center">
                                                <textarea 
                                                    class="w-full bg-transparent border-transparent rounded focus:border-indigo-500 focus:ring-indigo-500 p-1 resize-none text-sm transition-colors hover:bg-gray-100 focus:bg-white"
                                                    rows="1"
                                                    :value="keyData.values[locale.code] || ''" 
                                                    @change="updateValue(keyData, locale.code, $event.target.value)"
                                                    @focus="$event.target.rows = 3"
                                                    @blur="$event.target.rows = 1"
                                                ></textarea>
                                                <span v-if="!keyData.values[locale.code]" class="absolute right-2 top-2 pointer-events-none">
                                                    <span class="h-2 w-2 block rounded-full bg-red-400" title="Missing translation"></span>
                                                </span>
                                                <span v-if="savingState[keyData.id + '-' + locale.code]" class="absolute right-2 bottom-2 pointer-events-none">
                                                    <svg class="animate-spin h-3 w-3 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </span>
                                             </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Pagination Controls -->
                            <div v-if="pagination.total > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between sticky bottom-0 z-20">
                                <div class="text-sm text-gray-500">
                                    Showing @{{ (pagination.current_page - 1) * pagination.per_page + 1 }} to @{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of @{{ pagination.total }} results
                                </div>
                                <div class="flex gap-2">
                                    <button 
                                        @click="changePage(pagination.current_page - 1)" 
                                        :disabled="pagination.current_page <= 1"
                                        class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Previous
                                    </button>
                                    <button 
                                        @click="changePage(pagination.current_page + 1)" 
                                        :disabled="pagination.current_page >= pagination.last_page"
                                        class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                          <!-- Add Key Modal -->
                          <div v-if="showAddKeyModal" class="relative z-50">
                             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                             <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                               <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                 <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                     <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                         <div class="sm:flex sm:items-start">
                                             <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                 <h3 class="text-base font-semibold leading-6 text-gray-900">Add New Key</h3>
                                                 <div class="mt-4 space-y-4">
                                                     <div>
                                                         <label class="block text-sm font-medium leading-6 text-gray-900">Group</label>
                                                         <div class="mt-2">
                                                             <input v-model="newKey.group" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="e.g. messages">
                                                         </div>
                                                     </div>
                                                     <div>
                                                         <label class="block text-sm font-medium leading-6 text-gray-900">Key</label>
                                                         <div class="mt-2">
                                                             <input v-model="newKey.key" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="e.g. welcome_message">
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                         <button @click="createKey" type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Create</button>
                                         <button @click="showAddKeyModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                                     </div>
                                 </div>
                               </div>
                             </div>
                          </div>
                          
                          <!-- Add Locale Modal -->
                          <div v-if="showAddLocaleModal" class="relative z-50">
                             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                             <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                               <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                 <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                     <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                         <div class="sm:flex sm:items-start">
                                             <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                 <h3 class="text-base font-semibold leading-6 text-gray-900">Add Language</h3>
                                                 <div class="mt-2 mb-4">
                                                     <div class="flex flex-wrap gap-2">
                                                         <span v-for="l in locales" :key="l.id" class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">@{{l.code}}</span>
                                                     </div>
                                                 </div>
                                                 <div class="space-y-4">
                                                     <div>
                                                         <label class="block text-sm font-medium leading-6 text-gray-900">Locale Code</label>
                                                         <div class="mt-2">
                                                             <input v-model="newLocale.code" type="text" placeholder="e.g. es, de-DE" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                         </div>
                                                     </div>
                                                     <div class="relative flex gap-x-3 text-left">
                                                         <div class="flex h-6 items-center">
                                                             <input id="addLocaleIsDefault" v-model="newLocale.is_default" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                                         </div>
                                                         <div class="text-sm leading-6">
                                                             <label for="addLocaleIsDefault" class="font-medium text-gray-900">Set as Default Language</label>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                         <button @click="createLocale" type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Add Language</button>
                                         <button @click="showAddLocaleModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                                     </div>
                                 </div>
                               </div>
                             </div>
                          </div>

                          <!-- Edit Key Modal -->
                          <div v-if="showEditKeyModal" class="relative z-50">
                             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                             <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                               <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                 <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                     <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                         <div class="sm:flex sm:items-start">
                                             <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                  <h3 class="text-base font-semibold leading-6 text-gray-900">Edit Key</h3>
                                                  <div class="mt-4 space-y-4">
                                                     <div>
                                                         <label class="block text-sm font-medium leading-6 text-gray-900">Group</label>
                                                         <div class="mt-2">
                                                             <input v-model="editingKey.group" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                         </div>
                                                     </div>
                                                     <div>
                                                         <label class="block text-sm font-medium leading-6 text-gray-900">Key</label>
                                                         <div class="mt-2">
                                                             <input v-model="editingKey.key" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                         </div>
                                                     </div>
                                                  </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                         <button @click="updateKey" type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Save Changes</button>
                                         <button @click="showEditKeyModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                                     </div>
                                 </div>
                               </div>
                             </div>
                          </div>
                          <!-- Edit Locale Modal -->
                          <div v-if="showEditLocaleModal" class="relative z-50">
                             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                             <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                               <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                 <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                     <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                         <div class="sm:flex sm:items-start">
                                             <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                 <h3 class="text-base font-semibold leading-6 text-gray-900">Edit Language</h3>
                                                 <div class="mt-4 space-y-4">
                                                     <div>
                                                         <label class="block text-sm font-medium leading-6 text-gray-900">Locale Code</label>
                                                         <div class="mt-2">
                                                             <input v-model="editingLocale.code" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                         </div>
                                                     </div>
                                                     <div class="relative flex gap-x-3 text-left">
                                                         <div class="flex h-6 items-center">
                                                             <input id="editLocaleIsDefault" v-model="editingLocale.is_default" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                                         </div>
                                                         <div class="text-sm leading-6">
                                                             <label for="editLocaleIsDefault" class="font-medium text-gray-900">Set as Default Language</label>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                         <button @click="updateLocale" type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Save Changes</button>
                                         <button @click="showEditLocaleModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                                     </div>
                                 </div>
                               </div>
                             </div>
                          </div>
                         <!-- Confirmation Modal -->
                          <div v-if="confirmModal.show" class="relative z-[60]">
                             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                             <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                               <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                 <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                     <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                         <div class="sm:flex sm:items-start">
                                             <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                 <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                     <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                 </svg>
                                             </div>
                                             <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                 <h3 class="text-base font-semibold leading-6 text-gray-900">Confirm Action</h3>
                                                 <div class="mt-2">
                                                     <p class="text-sm text-gray-500">@{{ confirmModal.message }}</p>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                         <button @click="confirmModal.confirm" type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</button>
                                         <button @click="confirmModal.show = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                                     </div>
                                 </div>
                               </div>
                             </div>
                          </div>
                         
            <!-- Import Modal -->
             <div v-if="showImportModal" class="relative z-50">
                 <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                 <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                     <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                         <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                             <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                 <div class="sm:flex sm:items-start">
                                     <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                         <h3 class="text-base font-semibold leading-6 text-gray-900">Import Translations</h3>
                                         <div class="mt-4 space-y-4">
                                             <div>
                                                 <label class="block text-sm font-medium leading-6 text-gray-900">Target Locale</label>
                                                 <div class="mt-2">
                                                     <select v-model="importData.locale" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-full sm:text-sm sm:leading-6">
                                                         <option value="" disabled selected>Select a language</option>
                                                         <option v-for="loc in locales" :key="loc.id" :value="loc.code">
                                                             @{{ loc.name }} (@{{ loc.code }})
                                                         </option>
                                                     </select>
                                                 </div>
                                             </div>
                                             <div>
                                                 <label class="block text-sm font-medium leading-6 text-gray-900">JSON File</label>
                                                 <div 
                                                     class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 hover:border-indigo-400 transition-colors cursor-pointer bg-gray-50"
                                                     @click="$refs.fileInput.click()"
                                                     @dragover.prevent=""
                                                     @drop.prevent="handleFileDrop"
                                                 >
                                                     <div class="text-center">
                                                         <svg v-if="!importData.file" class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                             <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                                                         </svg>
                                                         <div v-if="!importData.file" class="mt-4 flex text-sm leading-6 text-gray-600 justify-center">
                                                             <span class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                                                 <span>Upload a file</span>
                                                             </span>
                                                             <span class="pl-1">or drag and drop</span>
                                                         </div>
                                                         <div v-if="importData.file" class="text-sm text-gray-900">
                                                             <p class="font-semibold">@{{ importData.file.name }}</p>
                                                             <p class="text-xs text-gray-500">@{{ (importData.file.size / 1024).toFixed(2) }} KB</p>
                                                             <button @click.stop="importData.file = null; $refs.fileInput.value = ''" class="text-red-600 hover:text-red-500 text-xs mt-2">Remove</button>
                                                         </div>
                                                         <p v-if="!importData.file" class="text-xs leading-5 text-gray-600">JSON up to 10MB</p>
                                                     </div>
                                                     <input type="file" ref="fileInput" @change="handleFileChange" accept=".json" class="hidden">
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                             <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                 <button 
                                     @click="executeImport" 
                                     type="button" 
                                     :disabled="!importData.locale || !importData.file || importing" 
                                     class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                                 >
                                     <svg v-if="importing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                         <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                         <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                     </svg>
                                     <span v-if="importing">Importing...</span>
                                     <span v-else>Import</span>
                                 </button>
                                 <button @click="showImportModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Export Modal -->
              <div v-if="showExportModal" class="relative z-50">
                  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showExportModal=false"></div>
                  <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                          <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                              <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                  <div class="sm:flex sm:items-start">
                                      <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                          <h3 class="text-base font-semibold leading-6 text-gray-900">Export Translations</h3>
                                          <div class="mt-4 space-y-4">
                                              <div>
                                                  <label class="block text-sm font-medium leading-6 text-gray-900">Language</label>
                                                  <div class="mt-2">
                                                      <select v-model="exportForm.locale" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-full sm:text-sm sm:leading-6">
                                                          <option value="" disabled selected>Select a language</option>
                                                          <option v-for="locale in locales" :key="locale.id" :value="locale.code">@{{ locale.name }}</option>
                                                      </select>
                                                  </div>
                                              </div>
                                              <div class="relative flex gap-x-3 text-left">
                                                  <div class="flex h-6 items-center">
                                                    <input id="nested" v-model="exportForm.nested" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                                  </div>
                                                  <div class="text-sm leading-6">
                                                    <label for="nested" class="font-medium text-gray-900">Nested JSON</label>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                  <button @click="exportTranslations" :disabled="!exportForm.locale" type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                      Export
                                  </button>
                                  <button @click="showExportModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

                          <!-- Toast Container -->
                         <div class="fixed top-4 right-4 z-[70] flex flex-col gap-2 pointer-events-none">
                            <transition-group name="toast">
                                <div v-for="toast in toasts" :key="toast.id" 
                                    class="pointer-events-auto flex items-center w-full max-w-xs p-4 space-x-3 text-gray-500 bg-white rounded-lg shadow-lg border border-gray-100"
                                    :class="{'border-l-4 border-l-green-500': toast.type === 'success', 'border-l-4 border-l-red-500': toast.type === 'error'}"
                                >
                                    <div v-if="toast.type === 'success'" class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    </div>
                                    <div v-if="toast.type === 'error'" class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                    <div class="text-sm font-normal">@{{ toast.message }}</div>
                                </div>
                            </transition-group>
                         </div>
                    </div>
                `,
            setup() {
                const keys = ref([]);
                // UI States
                const confirmModal = ref({ show: false, message: '', confirm: null });
                const toasts = ref([]);

                const notify = (message, type = 'success') => {
                    const id = Date.now();
                    toasts.value.push({ id, message, type });
                    setTimeout(() => {
                        toasts.value = toasts.value.filter(t => t.id !== id);
                    }, 3000);
                };
                const locales = ref([]);
                const loading = ref(true);
                const search = ref('');
                const loadingAction = ref(null);
                const savingState = ref({});

                const showAddKeyModal = ref(false);
                const newKey = ref({ group: 'messages', key: '' });

                const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
                const searchTimer = ref(null);

                const showExportModal = ref(false);
                const exportForm = ref({
                    locale: '',
                    nested: true
                });

                const exportTranslations = () => {
                    if (!exportForm.value.locale) return;

                    const url = `/${config.prefix}/export?locale=${exportForm.value.locale}&nested=${exportForm.value.nested ? 1 : 0}`;
                    window.location.href = url;
                    showExportModal.value = false;
                };



                // Filters
                const filters = ref({
                    group: '',
                    missing_locale: ''
                });
                const groups = ref([]);

                const fetchGroups = async () => {
                    try {
                        const res = await api.get('/groups');
                        groups.value = res.data || res; // handle array response
                    } catch (e) {
                        console.error("Failed to load groups", e);
                    }
                };

                const fetchKeys = async (page = 1) => {
                    loading.value = true;
                    try {
                        let url = `/translations/grouped?page=${page}`;
                        if (search.value) url += `&search=${encodeURIComponent(search.value)}`;
                        if (filters.value.group) url += `&group=${encodeURIComponent(filters.value.group)}`;
                        if (filters.value.missing_locale) url += `&missing_locale=${encodeURIComponent(filters.value.missing_locale)}`;

                        const data = await api.get(url);
                        keys.value = data.data; // API Resource puts list in 'data'
                        if (data.meta) {
                            pagination.value = data.meta;
                        }
                        // Always update locales to ensure we have the latest list
                        if (data.locales) {
                            locales.value = data.locales;
                        }
                    } catch (e) {
                        console.error("Data load failed:", e);
                        notify(`Failed to load data: ${e.message}`, 'error');
                    } finally {
                        loading.value = false;
                    }
                };

                // Alias loadData to fetchKeys for compatibility if needed elsewhere, 
                // but we should use fetchKeys primarily.
                const loadData = fetchKeys;

                const debouncedSearch = () => {
                    clearTimeout(searchTimer.value);
                    searchTimer.value = setTimeout(() => {
                        pagination.value.current_page = 1;
                        fetchKeys(1);
                    }, 300);
                };

                onMounted(() => {
                    fetchKeys(1);
                    fetchGroups();
                });


                const changePage = (page) => {
                    if (page >= 1 && page <= pagination.value.last_page) {
                        loadData(page);
                    }
                };

                const updateValue = async (keyData, locale, value) => {
                    const stateKey = keyData.id + '-' + locale;
                    savingState.value[stateKey] = true;

                    try {
                        await api.put('/translations/value', {
                            key_id: keyData.id,
                            locale: locale,
                            value: value
                        });
                        keyData.values[locale] = value;
                    } catch (e) {
                        console.error(e);
                        notify('Failed to save value', 'error');
                    } finally {
                        delete savingState.value[stateKey];
                    }
                };

                const createKey = async () => {
                    try {
                        const res = await api.post('/keys', newKey.value);
                        if (res.status === 'success') {
                            showAddKeyModal.value = false;
                            newKey.value.key = '';
                            await loadData();
                            notify('Key created successfully');
                        }
                    } catch (e) {
                        notify('Error creating key: ' + e.message, 'error');
                    }
                };

                const showAddLocaleModal = ref(false);
                const newLocale = ref({ code: '', is_default: false });

                const createLocale = async () => {
                    if (!newLocale.value.code) return;
                    try {
                        const res = await api.post('/locales', {
                            locale: newLocale.value.code,
                            is_default: newLocale.value.is_default
                        });
                        if (res.status === 'success') {
                            locales.value.push(res.locale); // Push full object
                            newLocale.value = { code: '', is_default: false };
                            showAddLocaleModal.value = false;
                            await loadData();
                            notify('Locale added successfully');
                        }
                    } catch (e) {
                        notify('Error creating locale: ' + e.message, 'error');
                    }
                };


                const showEditLocaleModal = ref(false);
                const editingLocale = ref({ id: null, code: '', is_default: false });

                const openEditLocaleModal = (locale) => {
                    editingLocale.value = { ...locale, is_default: !!locale.is_default };
                    showEditLocaleModal.value = true;
                };

                const updateLocale = async () => {
                    try {
                        const res = await api.put(`/locales/${editingLocale.value.id}`, {
                            code: editingLocale.value.code,
                            is_default: editingLocale.value.is_default
                        });
                        if (res.status === 'success') {
                            showEditLocaleModal.value = false;
                            await loadData();
                            notify('Locale updated successfully');
                        }
                    } catch (e) {
                        notify('Error updating locale: ' + e.message, 'error');
                    }
                };

                const deleteLocale = (id) => {
                    confirmModal.value = {
                        show: true,
                        message: 'Are you sure you want to delete this language? ALL translations for this language will be permanently deleted.',
                        confirm: async () => {
                            confirmModal.value.show = false;
                            try {
                                await api.delete(`/locales/${id}`);
                                await loadData();
                                notify('Locale deleted successfully');
                            } catch (e) {
                                notify('Error deleting locale: ' + e.message, 'error');
                            }
                        }
                    };
                };

                const showEditKeyModal = ref(false);
                const editingKey = ref({ id: null, group: '', key: '' });

                const openEditModal = (keyData) => {
                    editingKey.value = { ...keyData, key: keyData.key_name }; // Map key_name to key for the form
                    showEditKeyModal.value = true;
                };

                const updateKey = async () => {
                    try {
                        const res = await api.put(`/keys/${editingKey.value.id}`, {
                            group: editingKey.value.group,
                            key: editingKey.value.key
                        });
                        if (res.status === 'success') {
                            showEditKeyModal.value = false;
                            await loadData();
                            notify('Key updated successfully');
                        }
                    } catch (e) {
                        notify('Error updating key: ' + e.message, 'error');
                    }
                };

                const deleteKey = (id) => {
                    confirmModal.value = {
                        show: true,
                        message: 'Are you sure you want to delete this key? All translations associated with it will be permanently removed.',
                        confirm: async () => {
                            confirmModal.value.show = false;
                            try {
                                await api.delete(`/keys/${id}`);
                                await loadData();
                                notify('Key deleted successfully');
                            } catch (e) {
                                notify('Error deleting key: ' + e.message, 'error');
                            }
                        }
                    };
                };

                // Import Logic
                const showImportModal = ref(false);
                const importData = ref({ locale: '', file: null });
                const importing = ref(false);

                const handleFileChange = (event) => {
                    const file = event.target.files[0];
                    if (file) {
                        importData.value.file = file;
                    }
                };

                const executeImport = async () => {
                    if (!importData.value.locale || !importData.value.file) {
                        notify('Please select a locale and a file.', 'error');
                        return;
                    }

                    importing.value = true;
                    // Use FormData for file upload
                    const formData = new FormData();
                    formData.append('locale', importData.value.locale);
                    formData.append('file', importData.value.file);

                    try {
                        const res = await api.post('/import', formData, true); // true for multipart
                        notify(`Import successful! processed ${res.count} strings.`);
                        showImportModal.value = false;
                        importData.value = { locale: '', file: null }; // Reset
                        loadData(); // Reload grid
                    } catch (e) {
                        // Error handled by api util usually, but just in case
                        console.error(e);
                    } finally {
                        importing.value = false;
                    }
                };

                const handleFileDrop = (event) => {
                    const file = event.dataTransfer.files[0];
                    if (file && file.name.endsWith('.json')) {
                        importData.value.file = file;
                    } else if (file) {
                        notify('Only JSON files are allowed.', 'error');
                    }
                };

                return {
                    keys, locales, loading, search, debouncedSearch, updateValue, savingState,
                    filters, groups, fetchKeys, // Explicitly exposed
                    showAddKeyModal, newKey, createKey,
                    showAddLocaleModal, newLocale, createLocale,
                    showEditKeyModal, editingKey, openEditModal, updateKey, deleteKey,
                    showEditLocaleModal, editingLocale, openEditLocaleModal, updateLocale, deleteLocale,
                    toasts, confirmModal, pagination, changePage,
                    showImportModal, importData, handleFileChange, handleFileDrop, executeImport, importing,
                    showExportModal, exportForm, exportTranslations
                };
            }
        };

        const app = createApp({
            components: { TranslationsView },
            setup() {
                // Hide loading fallback once mounted
                onMounted(() => {
                    document.getElementById('loading-fallback')?.remove();
                });
                return { currentPage: 'TranslationsView' };
            }
        });

        app.mount('#app');
        console.log('Glosa: Vue app mounted.');

    } catch (err) {
        console.error('Glosa: Vue Init Error', err);
        document.body.innerHTML += '<div class="p-10 text-red-600 bg-white border m-10">Application Error: ' + err.message + '</div>';
    }
</script>