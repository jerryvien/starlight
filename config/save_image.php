<?php
if (isset($_POST['img'], $_POST['serial'], $_POST['agent'], $_POST['customer'], $_POST['purchase_date'])) {
    $img = $_POST['img'];
    $serialNumber = $_POST['serial'];
    $agentName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['agent']); // Sanitize folder name
    $customerName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['customer']); // Sanitize folder name
    $purchaseDate = $_POST['purchase_date'];
    
    // Remove the "data:image/jpeg;base64," part from the image data
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    // Define the public directory path
    $publicPath = __DIR__ . '/public';
    
    // Create the "Receipts" folder inside the public path
    $receiptsFolder = $publicPath . '/Receipts';
    if (!is_dir($receiptsFolder)) {
        mkdir($receiptsFolder, 0777, true);
    }

    // Create the main folder for the agent inside the "Receipts" folder
    $agentFolder = $receiptsFolder . '/' . $agentName;
    if (!is_dir($agentFolder)) {
        mkdir($agentFolder, 0777, true);
    }

    // Create the subfolder for the customer inside the agent's folder
    $customerFolder = $agentFolder . '/' . $customerName;
    if (!is_dir($customerFolder)) {
        mkdir($customerFolder, 0777, true);
    }

    // Define the file path for the image
    $fileName = $serialNumber . '_' . $purchaseDate . '.jpg';
    $filePath = $customerFolder . '/' . $fileName;
    
    // Save the image to the specified path
    if (file_put_contents($filePath, $data)) {
        echo "Image saved successfully at $filePath!";
    } else {
        echo "Failed to save the image.";
    }
} else {
    echo "Invalid request data.";
}
?>
