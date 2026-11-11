<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `language` varchar(10) NOT NULL DEFAULT \'en\',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `template_slug` varchar(255) DEFAULT NULL,
  `event_type` varchar(255) DEFAULT NULL,
  `related_entity_id` bigint(20) unsigned DEFAULT NULL,
  `related_entity_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'pending\',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_logs_recipient_email_index` (`recipient_email`),
  KEY `email_logs_status_index` (`status`),
  KEY `email_logs_event_type_index` (`event_type`),
  KEY `email_logs_sent_at_index` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `email_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `event_type` varchar(255) DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `status` varchar(255) NOT NULL DEFAULT \'queued\',
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 3,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_queue_status_index` (`status`),
  KEY `email_queue_scheduled_at_index` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        DB::statement('CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_is_read_index` (`is_read`),
  KEY `notifications_type_index` (`type`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_queue');
        Schema::dropIfExists('notifications');
    }
};
