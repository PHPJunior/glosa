@extends('glosa::layout')

@section('content')
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 z-10 w-full shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-600 text-white p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-gray-900">Glosa</h1>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-hidden flex flex-col relative">
            <div id="loading-fallback" class="absolute inset-0 flex items-center justify-center text-gray-500">
                Initializing Application...
            </div>

            <div class="w-full h-full flex flex-col overflow-hidden" v-cloak>
                <component :is="currentPage"></component>
            </div>
        </main>
    </div>
@endsection