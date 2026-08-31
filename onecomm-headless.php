<?php
/**
 * Plugin Name: 1Ecomm Headless Commerce
 * Description: Server-rendered catalog blocks for the 1Ecomm headless API.
 * Version: 0.1.0-preview.1
 * Requires PHP: 8.1
 * License: Proprietary
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/src/CatalogClient.php';
require_once __DIR__.'/src/Plugin.php';
Phessage\OneComm\Plugin::boot();
