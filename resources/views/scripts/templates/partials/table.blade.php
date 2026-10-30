@verbatim
<!-- Data Grid header (sticky) -->
<div v-else class="flex-1 overflow-auto custom-scrollbar relative">
    <table class="min-w-full divide-y divide-gray-200 border-separate border-spacing-0">
        <thead class="bg-gray-50 z-10">
            <tr>
                <th scope="col" class="sticky left-0 z-20 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                    Key
                </th>
                <th v-for="locale in locales" :key="locale.id" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 min-w-[300px] group">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span>{{ locale.code }}</span>
                            <span v-if="locale.is_default" class="bg-indigo-100 text-indigo-700 text-[10px] px-1.5 py-0.5 rounded border border-indigo-200">Default</span>
                        </div>
                        <!-- Locale Actions -->
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditLocaleModal(locale)" type="button" class="p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-gray-100" title="Edit Locale">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button @click="deleteLocale(locale.id)" type="button" class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-gray-100" title="Delete Locale">
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
                        <span class="text-indigo-600 font-mono">{{ keyData.key_name }}</span>
                        <span class="text-xs text-gray-400">{{ keyData.qualified_group ?? keyData.group }}</span>
                    </div>
                    <!-- Row Actions -->
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 flex gap-2 transition-opacity bg-white pl-2">
                        <button @click="openEditModal(keyData)" type="button" class="text-gray-400 hover:text-indigo-600" title="Edit Key">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button @click="deleteKey(keyData.id)" type="button" class="text-gray-400 hover:text-red-600" title="Delete Key">
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
    <div v-if="pagination.total > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4 sticky bottom-0 z-20">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="perPageSelect" class="text-sm font-medium text-gray-700">Per page:</label>
                <select
                        id="perPageSelect"
                        :value="pagination.per_page"
                        @change="changePerPage($event.target.value)"
                        class="rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-white"
                >
                    <option :value="10">10</option>
                    <option :value="20">20</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 mr-6">
                Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of {{ pagination.total }} results
            </span>
            <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                type="button"
                class="inline-flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Previous
            </button>
            <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                type="button"
                class="inline-flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Next
            </button>
        </div>
    </div>
</div>
@endverbatim
