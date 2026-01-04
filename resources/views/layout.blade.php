<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glosa TMS</title>
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar for horizontal table */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        [v-cloak] {
            display: none;
        }

        .toast-enter-active,
        .toast-leave-active {
            transition: all 0.3s ease;
        }

        .toast-enter-from,
        .toast-leave-to {
            opacity: 0;
            transform: translateX(30px);
        }

        .toast-enter-active,
        .toast-leave-active {
            transition: all 0.3s ease;
        }

        .toast-enter-from,
        .toast-leave-to {
            opacity: 0;
            transform: translateX(30px);
        }
    </style>
    <!-- Vue 3 -->
    <script type="importmap">
        {
          "imports": {
            "vue": "https://unpkg.com/vue@3/dist/vue.esm-browser.js"
          }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-900 antialiased">
    @yield('content')
    @include('glosa::scripts')
</body>

</html>