<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_number` varchar(50) NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` enum(\'US TAX\',\'UK TAX\',\'LEGAL\',\'GENERAL\') NOT NULL DEFAULT \'GENERAL\',
  `status` varchar(50) NOT NULL DEFAULT \'pending\',
  `priority` varchar(20) NOT NULL DEFAULT \'medium\',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_project_number_unique` (`project_number`),
  KEY `projects_client_id_foreign` (`client_id`),
  KEY `projects_invoice_id_foreign` (`invoice_id`),
  KEY `projects_assigned_by_foreign` (`assigned_by`),
  KEY `projects_created_by_foreign` (`created_by`),
  KEY `projects_status_index` (`status`),
  KEY `projects_department_index` (`department`),
  CONSTRAINT `projects_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `project_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_in_project` varchar(50) NOT NULL DEFAULT \'member\',
  `joined_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_members_project_id_foreign` (`project_id`),
  KEY `project_members_user_id_foreign` (`user_id`),
  CONSTRAINT `project_members_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `project_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_status_history_project_id_foreign` (`project_id`),
  KEY `project_status_history_changed_by_foreign` (`changed_by`),
  CONSTRAINT `project_status_history_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `project_status_history`');
        DB::statement('DROP TABLE IF EXISTS `project_members`');
        DB::statement('DROP TABLE IF EXISTS `projects`');
    }
};
