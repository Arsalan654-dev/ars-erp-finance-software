<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `compliance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'active\',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_records_created_by_foreign` (`created_by`),
  KEY `compliance_records_client_id_index` (`client_id`),
  KEY `compliance_records_expiry_date_index` (`expiry_date`),
  KEY `compliance_records_status_index` (`status`),
  KEY `compliance_records_document_type_index` (`document_type`),
  CONSTRAINT `compliance_records_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compliance_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `renewal_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `compliance_record_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `renewal_events_compliance_record_id_index` (`compliance_record_id`),
  KEY `renewal_events_event_type_index` (`event_type`),
  KEY `renewal_events_triggered_at_index` (`triggered_at`),
  CONSTRAINT `renewal_events_compliance_record_id_foreign` FOREIGN KEY (`compliance_record_id`) REFERENCES `compliance_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_records');
        Schema::dropIfExists('renewal_events');
    }
};
