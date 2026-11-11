<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `kpi_definitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `calculation_method` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) NOT NULL DEFAULT \'monthly\',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_definitions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `kpi_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kpi_definition_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `target_value` decimal(15,2) NOT NULL,
  `period` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpi_targets_kpi_definition_id_index` (`kpi_definition_id`),
  KEY `kpi_targets_user_id_index` (`user_id`),
  KEY `kpi_targets_period_index` (`period`),
  CONSTRAINT `kpi_targets_kpi_definition_id_foreign` FOREIGN KEY (`kpi_definition_id`) REFERENCES `kpi_definitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kpi_targets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `employee_kpi_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kpi_definition_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `actual_value` decimal(15,2) DEFAULT NULL,
  `target_value` decimal(15,2) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `period` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_result_unique` (`kpi_definition_id`,`user_id`,`period`),
  KEY `employee_kpi_results_kpi_definition_id_index` (`kpi_definition_id`),
  KEY `employee_kpi_results_user_id_index` (`user_id`),
  KEY `employee_kpi_results_period_index` (`period`),
  CONSTRAINT `employee_kpi_results_kpi_definition_id_foreign` FOREIGN KEY (`kpi_definition_id`) REFERENCES `kpi_definitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_kpi_results_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('kpi_targets');
        Schema::dropIfExists('employee_kpi_results');
    }
};
