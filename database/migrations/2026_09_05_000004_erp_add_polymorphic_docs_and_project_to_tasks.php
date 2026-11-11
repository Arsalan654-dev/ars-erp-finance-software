<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `documents`
  ADD COLUMN `attachable_type` varchar(255) DEFAULT NULL AFTER `compliance_record_id`,
  ADD COLUMN `attachable_id` bigint(20) unsigned DEFAULT NULL AFTER `attachable_type`,
  ADD INDEX `documents_attachable_index` (`attachable_type`, `attachable_id`)');

        DB::statement('ALTER TABLE `tasks`
  ADD COLUMN `project_id` bigint(20) unsigned DEFAULT NULL AFTER `client_id`,
  ADD KEY `tasks_project_id_foreign` (`project_id`),
  ADD CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `tasks` DROP FOREIGN KEY `tasks_project_id_foreign`');
        DB::statement('ALTER TABLE `tasks` DROP COLUMN `project_id`');
        DB::statement('ALTER TABLE `documents` DROP COLUMN `attachable_type`');
        DB::statement('ALTER TABLE `documents` DROP COLUMN `attachable_id`');
    }
};
