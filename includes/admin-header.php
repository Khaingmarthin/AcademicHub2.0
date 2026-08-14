<header class="bg-white shadow-md p-4 flex justify-between items-center">
    <h2 class="text-xl font-semibold text-gray-800">
        <?php echo isset($pageTitle) ? $pageTitle : "Admin Dashboard"; ?>
    </h2>
    <div>
        <a href="/" class="text-blue-600 hover:underline mr-4">View Site</a>
        <a href="/actions/student/logout.php" class="text-red-600 hover:underline">Logout</a>
    </div>
</header>
