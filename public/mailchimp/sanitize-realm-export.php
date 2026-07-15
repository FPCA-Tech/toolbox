<?php

declare(strict_types=1);

/**
 * Clean and format phone numbers to strict E.164 standard (+1XXXXXXXXXX)
 */
function sanitizeE164(string $rawPhone): string
{
  $clean = preg_replace('/[^0-9]/', '', $rawPhone);

  if (strlen($clean) === 10) {
    return '+1' . $clean;
  }
  if (strlen($clean) === 11 && $clean[0] === '1') {
    return '+' . $clean;
  }

  return $rawPhone;
}

// --- CSV Processing & Download Logic ---
// This checks if a file was uploaded to this exact script, regardless of its filename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['realm_csv'])) {
  $fileTmpPath = $_FILES['realm_csv']['tmp_name'];

  if (($handle = fopen($fileTmpPath, 'r')) !== false) {

    // Setup headers for immediate file download using the uploaded file's original name
    $outputFilename = 'sanitized_' . time() . '_' . $_FILES['realm_csv']['name'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $outputFilename . '"');

    $outputStream = fopen('php://output', 'w');

    $headers = fgetcsv($handle, 1000, ',');
    fputcsv($outputStream, $headers);

    $normalizedHeaders = array_map('strtolower', $headers);

    // Dynamically map the mobile/phone column
    $phoneColumnIndex = false;
    foreach (['mobile number', 'primary phone', 'phone', 'phone number', 'cell phone'] as $possibleHeader) {
      $index = array_search($possibleHeader, $normalizedHeaders, true);
      if ($index !== false) {
        $phoneColumnIndex = $index;
        break;
      }
    }

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
      if ($phoneColumnIndex !== false && isset($row[$phoneColumnIndex])) {
        $row[$phoneColumnIndex] = sanitizeE164($row[$phoneColumnIndex]);
      }
      fputcsv($outputStream, $row);
    }

    fclose($handle);
    fclose($outputStream);
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Phone Sanitizer Utility</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      max-width: 500px;
      margin: 60px auto;
      padding: 20px;
      background: #f4f6f8;
      color: #333;
    }

    .card {
      background: white;
      padding: 35px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    h2 {
      margin-top: 0;
      color: #111;
    }

    input[type="file"] {
      margin: 20px 0;
      display: block;
      width: 100%;
      padding: 10px;
      border: 1px dashed #ccc;
      border-radius: 5px;
      background: #fafafa;
      box-sizing: border-box;
    }

    button {
      background: #0070f3;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 500;
      width: 100%;
      transition: background 0.2s;
    }

    button:hover {
      background: #0051a8;
    }

    .note {
      font-size: 13px;
      color: #666;
      margin-top: 15px;
      line-height: 1.4;
    }
  </style>
</head>

<body>

  <div class="card">
    <h2>Phone Sanitizer Utility</h2>
    <p>Upload your 4-column CSV file. The script will automatically locate the mobile number column, format it to E.164, and download the cleaned file.</p>

    <!-- Setting action="" means it always posts to itself, no matter what you name this PHP file -->
    <form action="" method="POST" enctype="multipart/form-data">
      <input type="file" name="realm_csv" id="realm_csv" accept=".csv" required>
      <button type="submit">Sanitize & Download CSV</button>
    </form>

    <div class="note">
      All columns (first name, last name, email) will remain intact. Only the phone digits are modified for compatibility.
    </div>
  </div>

</body>

</html>