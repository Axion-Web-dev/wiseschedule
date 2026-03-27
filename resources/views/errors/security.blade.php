<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security Alert - ScheduleWise AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-3xl text-red-600" style="font-variation-settings: 'FILL' 1;">security</span>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-4">Security Alert</h1>

            <p class="text-gray-600 mb-8">{{ $message }}</p>

            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <span class="material-symbols-outlined text-lg mr-2 text-blue-600">tips_and_updates</span>
                    Security Tips
                </h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <span class="material-symbols-outlined text-green-600 text-sm mr-2 mt-0.5">check_circle</span>
                        Use a strong, unique password
                    </li>
                    <li class="flex items-start">
                        <span class="material-symbols-outlined text-green-600 text-sm mr-2 mt-0.5">check_circle</span>
                        Wait before trying again
                    </li>
                    <li class="flex items-start">
                        <span class="material-symbols-outlined text-green-600 text-sm mr-2 mt-0.5">check_circle</span>
                        Contact support if issues persist
                    </li>
                </ul>
            </div>

            <div class="space-y-3">
                <button onclick="history.back()" class="w-full bg-gray-900 text-white px-6 py-3 rounded-xl font-medium hover:bg-gray-800 transition-colors">
                    Go Back
                </button>
                <a href="/" class="block w-full bg-gray-100 text-gray-900 px-6 py-3 rounded-xl font-medium hover:bg-gray-200 transition-colors text-center">
                    Return Home
                </a>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500">
                    Need help? 
                    <a href="mailto:support@schedulewise.ai" class="text-blue-600 hover:text-blue-700 font-medium">
                        Contact Support
                    </a>
                </p>
            </div>
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">
                © {{ date('Y') }} ScheduleWise AI. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>