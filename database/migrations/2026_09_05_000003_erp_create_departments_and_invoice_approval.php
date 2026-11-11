<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `head_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_slug_unique` (`slug`),
  KEY `departments_head_id_foreign` (`head_id`),
  CONSTRAINT `departments_head_id_foreign` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `department_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_in_department` varchar(50) NOT NULL DEFAULT \'member\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_members_department_id_foreign` (`department_id`),
  KEY `department_members_user_id_foreign` (`user_id`),
  CONSTRAINT `department_members_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `invoice_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT \'pending\',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_approvals_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_approvals_approved_by_foreign` (`approved_by`),
  KEY `invoice_approvals_rejected_by_foreign` (`rejected_by`),
  CONSTRAINT `invoice_approvals_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_approvals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_approvals_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `invoice_approvals`');
        DB::statement('DROP TABLE IF EXISTS `department_members`');
        DB::statement('DROP TABLE IF EXISTS `departments`');
    }
};
