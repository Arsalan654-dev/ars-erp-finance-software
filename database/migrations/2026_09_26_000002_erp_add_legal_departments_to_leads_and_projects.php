<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departments = "'US TAX','UK TAX','LEGAL','Legal- US','Legal- UK','GENERAL'";
        $default = "'GENERAL'";

        DB::statement("ALTER TABLE `leads` MODIFY `department` enum({$departments}) DEFAULT {$default}");
        DB::statement("ALTER TABLE `projects` MODIFY `department` enum({$departments}) NOT NULL DEFAULT {$default}");
    }

    public function down(): void
    {
        // Move the new values back to LEGAL before narrowing the enum.
        DB::statement("UPDATE `leads` SET `department` = 'LEGAL' WHERE `department` IN ('Legal- US', 'Legal- UK')");
        DB::statement("UPDATE `projects` SET `department` = 'LEGAL' WHERE `department` IN ('Legal- US', 'Legal- UK')");

        $departments = "'US TAX','UK TAX','LEGAL','GENERAL'";
        $default = "'GENERAL'";

        DB::statement("ALTER TABLE `leads` MODIFY `department` enum({$departments}) DEFAULT {$default}");
        DB::statement("ALTER TABLE `projects` MODIFY `department` enum({$departments}) NOT NULL DEFAULT {$default}");
    }
};
