<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - UCSMTLA Academic Hub" : "UCSMTLA Academic Hub"; ?></title>
    <!-- Tailwind CSS (compiled) -->
    <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    <header class="bg-blue-800 text-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">UCSMTLA Hub</a>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="/" class="hover:text-blue-200">Home</a></li>
                    <li><a href="#" class="hover:text-blue-200">News</a></li>
                    <li><a href="#" class="hover:text-blue-200">Admissions</a></li>
                    <li><a href="#" class="hover:text-blue-200">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
