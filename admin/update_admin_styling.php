<?php
// This is a utility script to update all admin pages with the new styling
// It will scan the admin directory and update all PHP files with the new CSS links

// Define directory to scan
$adminDir = __DIR__;

// Define search and replace patterns
$searchPattern = '<link rel="stylesheet" href="(?:.*?)\/assets\/css\/bootstrap\.min\.css">\s*<link rel="stylesheet" href="(?:.*?)\/assets\/css\/(?:bootstrap-icons|.*?)\.css">';
$replacePattern = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/admin_modern.css">';

// Function to update a file
function updateFile($filePath, $searchPattern, $replacePattern) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return "Could not read file: $filePath";
    }
    
    // First try the main pattern replacement
    $updatedContent = preg_replace("/$searchPattern/is", $replacePattern, $content, -1, $count);
    
    // If no replacement was made, check for existing admin.css without modern
    if ($count === 0) {
        // Check for the standard syntax
        $adminCssPattern = '<link rel="stylesheet" href="(?:.*?)\/css\/admin\.css">';
        $adminCssReplace = '<link rel="stylesheet" href="<?= APP_URL ?>/css/admin.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/admin_modern.css">';
        $updatedContent = preg_replace("/$adminCssPattern/is", $adminCssReplace, $updatedContent, -1, $count1);
        
        // Check for the alternate syntax
        $altAdminCssPattern = '<link href="(?:.*?)\/css\/admin\.css" rel="stylesheet">';
        $altAdminCssReplace = '<link href="<?php echo APP_URL; ?>/css/admin.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/css/admin_modern.css" rel="stylesheet">';
        $updatedContent = preg_replace("/$altAdminCssPattern/is", $altAdminCssReplace, $updatedContent, -1, $count2);
        
        $count += ($count1 + $count2);
    }
    
    if ($count > 0) {
        if (file_put_contents($filePath, $updatedContent)) {
            return "Updated: $filePath ($count replacements)";
        } else {
            return "Failed to write to file: $filePath";
        }
    }
    return "No changes needed: $filePath";
}

// Scan directory for PHP files
$files = glob("$adminDir/*.php");
$results = [];

foreach ($files as $file) {
    $results[] = updateFile($file, $searchPattern, $replacePattern);
}

// Update the sidebar container in each file that still uses the old styling
$sidebarSearchPattern = '<div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse admin-sidebar">';
$sidebarReplacePattern = '<?php include \'../includes/admin_sidebar.php\'; ?>';

foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        $results[] = "Could not read file: $file";
        continue;
    }
    
    if (strpos($content, $sidebarSearchPattern) !== false) {
        // Replace the entire sidebar DIV with include
        $updatedContent = str_replace(
            $sidebarSearchPattern, 
            '<!-- Sidebar -->' . PHP_EOL . $sidebarReplacePattern,
            $content
        );
        
        if (file_put_contents($file, $updatedContent)) {
            $results[] = "Updated sidebar: $file";
        } else {
            $results[] = "Failed to update sidebar in: $file";
        }
    }
}

// Update main-content divs to add the main-content class
$mainContentSearchPattern = '<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">';
$mainContentReplacePattern = '<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">';

foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        $results[] = "Could not read file: $file";
        continue;
    }
    
    if (strpos($content, $mainContentSearchPattern) !== false) {
        $updatedContent = str_replace(
            $mainContentSearchPattern,
            $mainContentReplacePattern,
            $content
        );
        
        if (file_put_contents($file, $updatedContent)) {
            $results[] = "Updated main content div: $file";
        } else {
            $results[] = "Failed to update main content div in: $file";
        }
    }
}

// Output results
echo "<pre>";
echo "ADMIN STYLING UPDATE RESULTS:\n";
echo "==========================\n\n";
echo implode("\n", $results);
echo "</pre>";
