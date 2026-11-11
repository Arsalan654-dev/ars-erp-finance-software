<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `compliance_record_id` bigint(20) unsigned DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `storage_path` varchar(255) NOT NULL,
  `storage_disk` varchar(255) NOT NULL DEFAULT \'private\',
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `checksum` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `documents_client_id_index` (`client_id`),
  KEY `documents_category_index` (`category`),
  KEY `documents_compliance_record_id_index` (`compliance_record_id`),
  CONSTRAINT `documents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_compliance_record_id_foreign` FOREIGN KEY (`compliance_record_id`) REFERENCES `compliance_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `document_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `version_number` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `storage_path` varchar(255) NOT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `checksum` varchar(255) DEFAULT NULL,
  `change_notes` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_versions_document_id_version_number_unique` (`document_id`,`version_number`),
  KEY `document_versions_uploaded_by_foreign` (`uploaded_by`),
  KEY `document_versions_document_id_index` (`document_id`),
  CONSTRAINT `document_versions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_versions_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `document_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_access_logs_document_id_index` (`document_id`),
  KEY `document_access_logs_user_id_index` (`user_id`),
  KEY `document_access_logs_created_at_index` (`created_at`),
  CONSTRAINT `document_access_logs_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_access_logs');
    }
};
