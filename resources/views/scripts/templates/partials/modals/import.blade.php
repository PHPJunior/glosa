@verbatim
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
                                        <select v-model="importData.locale" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-white">
                                            <option value="" disabled selected>Select a language</option>
                                            <option v-for="loc in locales" :key="loc.id" :value="loc.code">
                                                {{ loc.name }} ({{ loc.code }})
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
                                                <p class="font-semibold">{{ importData.file.name }}</p>
                                                <p class="text-xs text-gray-500">{{ (importData.file.size / 1024).toFixed(2) }} KB</p>
                                                <button @click.stop="importData.file = null; $refs.fileInput.value = ''" type="button" class="text-red-600 hover:text-red-500 text-xs mt-2">Remove</button>
                                            </div>
                                            <p v-if="!importData.file" class="text-xs leading-5 text-gray-600">JSON up to 10MB</p>
                                        </div>
                                        <input type="file" ref="fileInput" @change="handleFileChange" accept=".json" class="hidden">
                                    </div>
                                </div>

                                <!-- Warning Notice -->
                                <div class="rounded-md bg-amber-50 p-3 ring-1 ring-inset ring-amber-500/20">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 text-xs text-amber-800">
                                            <p class="font-medium text-amber-900">Warning</p>
                                            <p class="mt-0.5">Importing translations will overwrite existing values for matching keys in this language.</p>
                                        </div>
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
                        class="inline-flex w-full justify-center items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <svg v-if="importing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="importing">Importing...</span>
                        <span v-else>Import</span>
                    </button>
                    <button @click="showImportModal=false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:mt-0 sm:w-auto transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endverbatim
