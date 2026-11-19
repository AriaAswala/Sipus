<?php
/**
 * File: templates/header.php
 * Deskripsi: Header HTML bersama untuk semua halaman dengan sidebar layout.
 * 
 * Variabel yang harus diset SEBELUM include:
 *   $page_title  - Judul halaman (string)
 *   $base_url    - Path relatif ke root sipus ('' untuk root, '../' untuk subfolder)
 */
$base_url = $base_url ?? '';
$page_title = $page_title ?? 'SIPUS';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?> - SIPUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $base_url; ?>assets/css/sidebar.css" rel="stylesheet">
</head>
<body class="sidebar-layout">
