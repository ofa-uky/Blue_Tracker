<?php

require 'vendor/autoload.php';  // Load Composer packages

// Load environment variables from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Fetch the GitHub API token from environment variables
$githubToken = $_ENV['GITHUB_API_TOKEN'];

// File: api.php

// Define the cache file, CSV file, and GitHub API URL
$cacheFile = 'cache.json';
$csvFile = 'data.csv';  // The CSV file hosted in the repository
$githubApiUrl = 'https://api.github.com/repos/your-repo/data';

// Function to fetch data from GitHub API
function fetchDataFromGitHub() {
    global $githubApiUrl;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $githubApiUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'GitHub-App');
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Function to load cached data
function loadCache() {
    global $cacheFile;
    if (file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    return [];
}

// Function to save data to cache
function saveCache($data) {
    global $cacheFile;
    file_put_contents($cacheFile, json_encode($data));
}

// Function to save data to CSV (long-term storage)
function saveToCSV($data) {
    global $csvFile;
    $csvHeader = ["Date", "Completed", "Expired", "Was In Progress", "Was Not Completed", "Not Completed", "Not Ready", "Opted-out"];

    // Check if CSV file exists, if not create it with headers
    if (!file_exists($csvFile)) {
        $file = fopen($csvFile, 'w');
        fputcsv($file, $csvHeader);
        fclose($file);
    }

    // Append new data to CSV
    $file = fopen($csvFile, 'a');
    $date = date('Y-m-d');
    $csvRow = [$date, $data['Completed'], $data['Expired'], $data['Was In Progress'], $data['Was Not Completed'], $data['Not Completed'], $data['Not Ready'], $data['Opted-out']];
    fputcsv($file, $csvRow);
    fclose($file);
}

// Function to calculate percentage change
function hasSignificantIncrease($newData, $oldData) {
    foreach ($newData as $key => $value) {
        if (isset($oldData[$key])) {
            $percentageChange = (($value - $oldData[$key]) / $oldData[$key]) * 100;
            if ($percentageChange > 5) {
                return true;
            }
        }
    }
    return false;
}

// Fetch new data from GitHub
$newData = fetchDataFromGitHub();

// Load previous cache
$oldData = loadCache();

// Compare new data with old data
$hasWarning = hasSignificantIncrease($newData, $oldData);

// Save new data to cache
saveCache($newData);

// Save new data to CSV for long-term storage
saveToCSV($newData);

// Return data as JSON with warning flag
echo json_encode([
    'data' => $newData,
    'warning' => $hasWarning
]);
?>
