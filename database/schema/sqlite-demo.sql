PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "attendance_records" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "employee_id" INTEGER NOT NULL,
  "date" TEXT NOT NULL,
  "clock_in" TEXT,
  "clock_out" TEXT,
  "status" TEXT NOT NULL DEFAULT '''present''',
  "notes" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "audit_logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER,
  "action" TEXT NOT NULL,
  "entity_type" TEXT,
  "entity_id" INTEGER,
  "ip_address" TEXT,
  "user_agent" TEXT,
  "metadata" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "bank_verifications" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "payment_id" INTEGER NOT NULL,
  "bank_name" TEXT,
  "account_number" TEXT,
  "transaction_reference" TEXT,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "notes" TEXT,
  "verified_by" INTEGER,
  "verified_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "clients" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_number" TEXT,
  "business_name" TEXT NOT NULL,
  "legal_name" TEXT,
  "email" TEXT,
  "phone" TEXT,
  "country" TEXT,
  "address" TEXT,
  "ein_encrypted" TEXT,
  "ein_hash" TEXT,
  "itin_encrypted" TEXT,
  "itin_hash" TEXT,
  "utr_encrypted" TEXT,
  "utr_hash" TEXT,
  "ssn_encrypted" TEXT,
  "ssn_hash" TEXT,
  "tax_type" TEXT,
  "status" TEXT NOT NULL DEFAULT '''active''',
  "notes" TEXT,
  "created_by" INTEGER,
  "assigned_to" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "client_contacts" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER NOT NULL,
  "first_name" TEXT NOT NULL,
  "last_name" TEXT NOT NULL,
  "email" TEXT,
  "phone" TEXT,
  "position" TEXT,
  "is_primary" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "client_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "action" TEXT NOT NULL,
  "metadata" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "client_notes" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "content" TEXT NOT NULL,
  "type" TEXT NOT NULL DEFAULT '''general''',
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "client_services" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER NOT NULL,
  "service_name" TEXT NOT NULL,
  "status" TEXT NOT NULL DEFAULT '''active''',
  "start_date" TEXT,
  "end_date" TEXT,
  "notes" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "compliance_records" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER NOT NULL,
  "document_type" TEXT NOT NULL,
  "title" TEXT,
  "reference_number" TEXT,
  "issuing_authority" TEXT,
  "issue_date" TEXT,
  "expiry_date" TEXT,
  "status" TEXT NOT NULL DEFAULT '''active''',
  "notes" TEXT,
  "created_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "departments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL,
  "slug" TEXT NOT NULL,
  "head_id" INTEGER,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "department_members" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "department_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "role_in_department" TEXT NOT NULL DEFAULT '''member''',
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "documents" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER,
  "compliance_record_id" INTEGER,
  "attachable_type" TEXT,
  "attachable_id" INTEGER,
  "filename" TEXT NOT NULL,
  "original_filename" TEXT NOT NULL,
  "mime_type" TEXT,
  "size" INTEGER,
  "storage_path" TEXT NOT NULL,
  "storage_disk" TEXT NOT NULL DEFAULT '''private''',
  "category" TEXT,
  "description" TEXT,
  "checksum" TEXT,
  "uploaded_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "document_access_logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "document_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "action" TEXT NOT NULL,
  "ip_address" TEXT,
  "user_agent" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "document_versions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "document_id" INTEGER NOT NULL,
  "version_number" INTEGER NOT NULL,
  "filename" TEXT NOT NULL,
  "storage_path" TEXT NOT NULL,
  "size" INTEGER,
  "checksum" TEXT,
  "change_notes" TEXT,
  "uploaded_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "email_logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "recipient_email" TEXT NOT NULL,
  "recipient_name" TEXT,
  "subject" TEXT NOT NULL,
  "template_slug" TEXT,
  "event_type" TEXT,
  "related_entity_id" INTEGER,
  "related_entity_type" TEXT,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "error_message" TEXT,
  "sent_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "email_queue" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "to_email" TEXT NOT NULL,
  "to_name" TEXT,
  "subject" TEXT NOT NULL,
  "body" TEXT NOT NULL,
  "event_type" TEXT,
  "attachments" TEXT,
  "status" TEXT NOT NULL DEFAULT '''queued''',
  "retry_count" INTEGER NOT NULL DEFAULT 0,
  "max_retries" INTEGER NOT NULL DEFAULT 3,
  "scheduled_at" TEXT,
  "processed_at" TEXT,
  "error" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "email_settings" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL,
  "host" TEXT NOT NULL,
  "port" INTEGER NOT NULL DEFAULT 587,
  "username" TEXT NOT NULL,
  "password" TEXT NOT NULL,
  "encryption" TEXT NOT NULL DEFAULT '''tls''',
  "from_address" TEXT NOT NULL,
  "from_name" TEXT NOT NULL DEFAULT '''''',
  "is_active" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "email_templates" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "slug" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "subject" TEXT NOT NULL,
  "body" TEXT NOT NULL,
  "variables" TEXT,
  "language" TEXT NOT NULL DEFAULT '''en''',
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "employees" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER NOT NULL,
  "first_name" TEXT NOT NULL,
  "last_name" TEXT NOT NULL,
  "phone" TEXT,
  "address" TEXT,
  "emergency_contact" TEXT,
  "emergency_phone" TEXT,
  "date_of_birth" TEXT,
  "joining_date" TEXT,
  "exit_date" TEXT,
  "employment_type" TEXT NOT NULL DEFAULT '''full_time''',
  "status" TEXT NOT NULL DEFAULT '''active''',
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "employee_documents" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "employee_id" INTEGER NOT NULL,
  "document_type" TEXT NOT NULL,
  "filename" TEXT NOT NULL,
  "original_filename" TEXT NOT NULL,
  "storage_path" TEXT NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "employee_kpi_results" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kpi_definition_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "actual_value" REAL,
  "target_value" REAL,
  "score" REAL,
  "period" TEXT NOT NULL,
  "notes" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "exchange_rates" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "from_currency" TEXT NOT NULL,
  "to_currency" TEXT NOT NULL DEFAULT '''PKR''',
  "rate" REAL NOT NULL,
  "date" TEXT NOT NULL DEFAULT 'curdate()',
  "source" TEXT NOT NULL DEFAULT '''manual''',
  "set_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "expenses" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "expense_number" TEXT NOT NULL,
  "category" TEXT,
  "currency" TEXT NOT NULL DEFAULT '''PKR''',
  "amount" REAL NOT NULL,
  "exchange_rate" REAL,
  "pkr_equivalent" REAL,
  "expense_date" TEXT,
  "description" TEXT,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "created_by" INTEGER,
  "approved_by" INTEGER,
  "approved_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "failed_jobs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "uuid" TEXT NOT NULL,
  "connection" TEXT NOT NULL,
  "queue" TEXT NOT NULL,
  "payload" TEXT NOT NULL,
  "exception" TEXT NOT NULL,
  "failed_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "invoices" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "invoice_number" TEXT NOT NULL,
  "client_id" INTEGER NOT NULL,
  "quotation_id" INTEGER,
  "duplicate_of_id" INTEGER,
  "currency" TEXT NOT NULL DEFAULT '''USD''',
  "exchange_rate" REAL,
  "subtotal" REAL NOT NULL DEFAULT 0.00,
  "discount" REAL NOT NULL DEFAULT 0.00,
  "tax" REAL NOT NULL DEFAULT 0.00,
  "total" REAL NOT NULL DEFAULT 0.00,
  "paid_amount" REAL NOT NULL DEFAULT 0.00,
  "balance" REAL NOT NULL DEFAULT 0.00,
  "status" TEXT NOT NULL DEFAULT '''draft''',
  "payment_status" TEXT NOT NULL DEFAULT '''unpaid''',
  "issue_date" TEXT,
  "due_date" TEXT,
  "locked_at" TEXT,
  "locked_by" INTEGER,
  "notes" TEXT,
  "terms" TEXT,
  "created_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "invoice_approvals" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "invoice_id" INTEGER NOT NULL,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "approved_by" INTEGER,
  "rejected_by" INTEGER,
  "notes" TEXT,
  "decided_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "invoice_items" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "invoice_id" INTEGER NOT NULL,
  "description" TEXT NOT NULL,
  "quantity" INTEGER NOT NULL DEFAULT 1,
  "unit_price" REAL NOT NULL DEFAULT 0.00,
  "total" REAL NOT NULL DEFAULT 0.00,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "invoice_status_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "invoice_id" INTEGER NOT NULL,
  "from_status" TEXT,
  "to_status" TEXT NOT NULL,
  "changed_by" INTEGER,
  "reason" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "kpi_definitions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "slug" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT,
  "unit" TEXT,
  "calculation_method" TEXT,
  "frequency" TEXT NOT NULL DEFAULT '''monthly''',
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "kpi_targets" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kpi_definition_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "target_value" REAL NOT NULL,
  "period" TEXT NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "leads" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "client_id" INTEGER,
  "source" TEXT NOT NULL DEFAULT '''whatsapp''',
  "campaign_name" TEXT,
  "is_duplicate" INTEGER NOT NULL DEFAULT 0,
  "duplicate_of_lead_id" INTEGER,
  "business_name" TEXT,
  "contact_name" TEXT,
  "email" TEXT,
  "phone" TEXT,
  "department" TEXT DEFAULT '''GENERAL''',
  "status" TEXT NOT NULL DEFAULT '''new''',
  "priority" TEXT NOT NULL DEFAULT '''medium''',
  "notes" TEXT,
  "assigned_to" INTEGER,
  "created_by" INTEGER,
  "converted_at" TEXT,
  "payment_type" TEXT,
  "payment_amount" REAL,
  "payment_currency" TEXT DEFAULT '''USD''',
  "payment_method" TEXT,
  "payment_reference" TEXT,
  "payment_date" TEXT,
  "payment_screenshot_path" TEXT,
  "payment_approval_note" TEXT,
  "payment_approved_by" INTEGER,
  "lost_reason" TEXT,
  "lost_note" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "lead_activities" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "lead_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "type" TEXT NOT NULL DEFAULT '''note''',
  "subject" TEXT,
  "body" TEXT,
  "metadata" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "lead_status_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "lead_id" INTEGER NOT NULL,
  "from_status" TEXT,
  "to_status" TEXT NOT NULL,
  "changed_by" INTEGER,
  "reason" TEXT,
  "created_at" TEXT
);
CREATE TABLE IF NOT EXISTS "leave_requests" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "employee_id" INTEGER NOT NULL,
  "approved_by" INTEGER,
  "type" TEXT NOT NULL,
  "start_date" TEXT NOT NULL,
  "end_date" TEXT NOT NULL,
  "total_days" INTEGER NOT NULL,
  "reason" TEXT,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "rejection_reason" TEXT,
  "approved_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "ledger_accounts" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "code" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "type" TEXT NOT NULL,
  "currency" TEXT,
  "description" TEXT,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "ledger_entries" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "ledger_account_id" INTEGER NOT NULL,
  "entry_type" TEXT NOT NULL,
  "amount" REAL NOT NULL,
  "currency" TEXT NOT NULL DEFAULT '''PKR''',
  "invoice_id" INTEGER,
  "payment_id" INTEGER,
  "expense_id" INTEGER,
  "description" TEXT,
  "entry_date" TEXT,
  "created_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "login_attempts" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "email" TEXT,
  "ip_address" TEXT,
  "success" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "migrations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "migration" TEXT NOT NULL,
  "batch" INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS "notifications" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER NOT NULL,
  "type" TEXT NOT NULL,
  "title" TEXT NOT NULL,
  "body" TEXT,
  "link" TEXT,
  "is_read" INTEGER NOT NULL DEFAULT 0,
  "read_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
  "email" TEXT NOT NULL,
  "token" TEXT NOT NULL,
  "created_at" TEXT,
  PRIMARY KEY ("email")
);
CREATE TABLE IF NOT EXISTS "payments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "payment_number" TEXT NOT NULL,
  "client_id" INTEGER NOT NULL,
  "lead_id" INTEGER,
  "currency" TEXT NOT NULL,
  "amount" REAL NOT NULL,
  "screenshot_amount" REAL,
  "amount_mismatch" INTEGER NOT NULL DEFAULT 0,
  "exchange_rate" REAL,
  "pkr_equivalent" REAL,
  "payment_method" TEXT,
  "reference_number" TEXT,
  "screenshot_path" TEXT,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "payment_date" TEXT,
  "notes" TEXT,
  "recorded_by" INTEGER,
  "verified_by" INTEGER,
  "acknowledged_by" INTEGER,
  "acknowledged_at" TEXT,
  "verified_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "payment_allocations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "payment_id" INTEGER NOT NULL,
  "invoice_id" INTEGER NOT NULL,
  "amount" REAL NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "payroll_records" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "employee_id" INTEGER NOT NULL,
  "period" TEXT NOT NULL,
  "currency" TEXT NOT NULL DEFAULT '''PKR''',
  "gross_amount" REAL NOT NULL,
  "deductions" REAL NOT NULL DEFAULT 0.00,
  "net_amount" REAL NOT NULL,
  "exchange_rate" REAL,
  "pkr_equivalent" REAL,
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "payment_date" TEXT,
  "notes" TEXT,
  "created_by" INTEGER,
  "approved_by" INTEGER,
  "approved_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "permissions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "slug" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "group" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "tokenable_type" TEXT NOT NULL,
  "tokenable_id" INTEGER NOT NULL,
  "name" TEXT NOT NULL,
  "token" TEXT NOT NULL,
  "abilities" TEXT,
  "last_used_at" TEXT,
  "expires_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "projects" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "project_number" TEXT NOT NULL,
  "client_id" INTEGER NOT NULL,
  "invoice_id" INTEGER,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "department" TEXT NOT NULL DEFAULT '''GENERAL''',
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "priority" TEXT NOT NULL DEFAULT '''medium''',
  "start_date" TEXT,
  "due_date" TEXT,
  "completed_at" TEXT,
  "assigned_by" INTEGER,
  "created_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "project_members" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "project_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "role_in_project" TEXT NOT NULL DEFAULT '''member''',
  "joined_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "project_status_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "project_id" INTEGER NOT NULL,
  "from_status" TEXT,
  "to_status" TEXT NOT NULL,
  "changed_by" INTEGER,
  "reason" TEXT,
  "created_at" TEXT
);
CREATE TABLE IF NOT EXISTS "quotations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "quotation_number" TEXT NOT NULL,
  "client_id" INTEGER NOT NULL,
  "duplicate_of_id" INTEGER,
  "currency" TEXT NOT NULL DEFAULT '''USD''',
  "exchange_rate" REAL,
  "subtotal" REAL NOT NULL DEFAULT 0.00,
  "discount" REAL NOT NULL DEFAULT 0.00,
  "tax" REAL NOT NULL DEFAULT 0.00,
  "total" REAL NOT NULL DEFAULT 0.00,
  "status" TEXT NOT NULL DEFAULT '''draft''',
  "valid_until" TEXT,
  "notes" TEXT,
  "terms" TEXT,
  "created_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "quotation_items" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "quotation_id" INTEGER NOT NULL,
  "description" TEXT NOT NULL,
  "quantity" INTEGER NOT NULL DEFAULT 1,
  "unit_price" REAL NOT NULL DEFAULT 0.00,
  "total" REAL NOT NULL DEFAULT 0.00,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "quotation_status_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "quotation_id" INTEGER NOT NULL,
  "from_status" TEXT,
  "to_status" TEXT NOT NULL,
  "changed_by" INTEGER,
  "reason" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "renewal_events" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "compliance_record_id" INTEGER NOT NULL,
  "event_type" TEXT NOT NULL,
  "triggered_at" TEXT NOT NULL DEFAULT 'current_timestamp()',
  "metadata" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "roles" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "role_permissions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "role_id" INTEGER NOT NULL,
  "permission_id" INTEGER NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "role_users" (
  "user_id" INTEGER NOT NULL,
  "role_id" INTEGER NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT,
  PRIMARY KEY ("user_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "sales_packages" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL,
  "description" TEXT,
  "unit_price" REAL NOT NULL DEFAULT 0.00,
  "currency" TEXT NOT NULL DEFAULT '''USD''',
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "sales_package_items" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "sales_package_id" INTEGER NOT NULL,
  "description" TEXT NOT NULL,
  "quantity" INTEGER NOT NULL DEFAULT 1,
  "unit_price" REAL NOT NULL DEFAULT 0.00,
  "sort_order" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "security_events" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER,
  "event_type" TEXT NOT NULL,
  "ip_address" TEXT,
  "user_agent" TEXT,
  "metadata" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "sessions" (
  "id" TEXT NOT NULL,
  "user_id" INTEGER,
  "ip_address" TEXT,
  "user_agent" TEXT,
  "payload" TEXT NOT NULL,
  "last_activity" INTEGER NOT NULL,
  PRIMARY KEY ("id")
);
CREATE TABLE IF NOT EXISTS "settings" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "group" TEXT NOT NULL DEFAULT '''general''',
  "key" TEXT NOT NULL,
  "value" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "sop_documents" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "title" TEXT NOT NULL,
  "category" TEXT,
  "version" TEXT NOT NULL DEFAULT '''1.0''',
  "content" TEXT,
  "filename" TEXT,
  "department" TEXT,
  "status" TEXT NOT NULL DEFAULT '''active''',
  "created_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "tasks" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "task_number" TEXT NOT NULL,
  "client_id" INTEGER,
  "project_id" INTEGER,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "department" TEXT,
  "priority" TEXT NOT NULL DEFAULT '''medium''',
  "status" TEXT NOT NULL DEFAULT '''pending''',
  "start_date" TEXT,
  "due_date" TEXT,
  "completed_at" TEXT,
  "created_by" INTEGER,
  "completed_by" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "deleted_at" TEXT
);
CREATE TABLE IF NOT EXISTS "task_assignments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "task_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "assigned_by" TEXT,
  "assigned_at" TEXT NOT NULL DEFAULT 'current_timestamp()',
  "unassigned_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "task_attachments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "task_id" INTEGER NOT NULL,
  "filename" TEXT NOT NULL,
  "original_filename" TEXT NOT NULL,
  "mime_type" TEXT,
  "size" INTEGER,
  "storage_path" TEXT NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "task_comments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "task_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "content" TEXT NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "task_escalations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "task_id" INTEGER NOT NULL,
  "escalation_level" TEXT NOT NULL DEFAULT '''1''',
  "reason" TEXT,
  "escalated_to" INTEGER,
  "escalated_at" TEXT NOT NULL DEFAULT 'current_timestamp()',
  "resolved_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "task_status_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "task_id" INTEGER NOT NULL,
  "from_status" TEXT,
  "to_status" TEXT NOT NULL,
  "changed_by" INTEGER,
  "reason" TEXT,
  "created_at" TEXT NOT NULL DEFAULT 'current_timestamp()'
);
CREATE TABLE IF NOT EXISTS "training_assignments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "training_course_id" INTEGER NOT NULL,
  "employee_id" INTEGER NOT NULL,
  "assigned_date" TEXT,
  "completed_date" TEXT,
  "status" TEXT NOT NULL DEFAULT '''assigned''',
  "score" REAL,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "training_courses" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "category" TEXT,
  "duration_hours" INTEGER,
  "status" TEXT NOT NULL DEFAULT '''active''',
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "users" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "employee_id" TEXT,
  "name" TEXT NOT NULL,
  "email" TEXT NOT NULL,
  "email_verified_at" TEXT,
  "password" TEXT NOT NULL,
  "remember_token" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT,
  "phone" TEXT,
  "department" TEXT,
  "designation" TEXT,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "must_change_password" INTEGER NOT NULL DEFAULT 0,
  "two_factor_enabled" INTEGER NOT NULL DEFAULT 0,
  "two_factor_code" TEXT,
  "two_factor_expires_at" TEXT,
  "two_factor_recovery_codes" TEXT,
  "active_session_id" TEXT,
  "last_activity_at" TEXT,
  "timezone" TEXT,
  "locale" TEXT NOT NULL DEFAULT '''en'''
);
CREATE TABLE IF NOT EXISTS "user_permissions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER NOT NULL,
  "permission_id" INTEGER NOT NULL,
  "type" TEXT NOT NULL DEFAULT '''allow''',
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE TABLE IF NOT EXISTS "whatsapp_accounts" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL,
  "provider" TEXT NOT NULL DEFAULT '''meta_cloud''',
  "phone_number_id" TEXT NOT NULL,
  "api_url" TEXT,
  "api_token" TEXT NOT NULL,
  "verify_token" TEXT,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);
COMMIT;
